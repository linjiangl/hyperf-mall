<?php

/**
 * WeEngine Team
 *
 * (c) We7Team 2019 <https://www.w7.cc>
 *
 * This is not a free software
 * Using it under the license terms
 * visited https://www.w7.cc for more details
 */

namespace App\Core\Plugins;

use App\Core\Plugins\Bucket\QiniuBucket;
use Hyperf\HttpMessage\Upload\UploadedFile;

class UEditor
{
	private $fileField; //文件域名
	private $file; //文件上传对象
	private $base64; //文件上传对象
	private $config; //配置信息
	private $oriName; //原始文件名
	private $fileName; //新文件名
	private $fullName; //完整文件名,即从当前配置目录开始的URL
	private $filePath; //完整文件名,即从当前配置目录开始的URL
	private $fileSize; //文件大小
	private $fileType; //文件类型
	private $stateInfo; //上传状态信息,
	private $stateMap = array( //上传状态映射表，国际化用户需考虑此处数据的国际化
		'SUCCESS', //上传成功标记，在UEditor中内不可改变，否则flash判断会出错
		'文件大小超出 upload_max_filesize 限制',
		'文件大小超出 MAX_FILE_SIZE 限制',
		'文件未被完整上传',
		'没有文件被上传',
		'上传文件为空',
		'ERROR_TMP_FILE' => '临时文件错误',
		'ERROR_TMP_FILE_NOT_FOUND' => '找不到临时文件',
		'ERROR_SIZE_EXCEED' => '文件大小超出网站限制',
		'ERROR_TYPE_NOT_ALLOWED' => '文件类型不允许',
		'ERROR_CREATE_DIR' => '目录创建失败',
		'ERROR_DIR_NOT_WRITEABLE' => '目录没有写权限',
		'ERROR_FILE_MOVE' => '文件保存时出错',
		'ERROR_FILE_NOT_FOUND' => '找不到上传文件',
		'ERROR_WRITE_CONTENT' => '写入文件内容错误',
		'ERROR_UNKNOWN' => '未知错误',
		'ERROR_DEAD_LINK' => '链接不可用',
		'ERROR_HTTP_LINK' => '链接不是http链接',
		'ERROR_HTTP_CONTENTTYPE' => '链接contentType不正确',
		'INVALID_URL' => '非法 URL',
		'INVALID_IP' => '非法 IP'
	);

	/**
	 * 构造函数
	 * @param string $fileField 表单名称
	 * @param array $config 配置项
	 * @param string $type 类型
	 */
	public function __construct($fileField, $config, $type = 'upload')
	{
		$this->fileField = $fileField;
		$this->config = $config;
		if ($type == 'remote') {
			$this->saveRemote();
		} elseif ($type == 'base64') {
			$this->upBase64();
		} else {
			$this->upFile();
		}

		$this->stateMap['ERROR_TYPE_NOT_ALLOWED'] = iconv('unicode', 'utf-8', $this->stateMap['ERROR_TYPE_NOT_ALLOWED']);
	}

	/**
	 * 上传文件的主处理方法
	 */
	private function upFile()
	{
		/** @var UploadedFile $file */
		$file = $uploadFile = request()->getUploadedFiles()[$this->fileField];
		if ($file instanceof UploadedFile) {
			$this->file = $file;
		}
		if (!$file) {
			$this->stateInfo = $this->getStateInfo('ERROR_FILE_NOT_FOUND');
			return;
		}
		if ($file->getError()) {
			$this->stateInfo = $this->getStateInfo($file->getError());
			return;
		} elseif (!file_exists($file->getRealPath())) {
			$this->stateInfo = $this->getStateInfo('ERROR_TMP_FILE_NOT_FOUND');
			return;
		} elseif (!is_uploaded_file($file->getRealPath())) {
			$this->stateInfo = $this->getStateInfo('ERROR_TMPFILE');
			return;
		}

		$this->oriName = $file->getClientFilename();
		$this->fileSize = $file->getSize();
		$this->fileType = $this->getFileExt();
		$this->fullName = $this->getFullName();
		$this->filePath = $this->getFilePath();
		$this->fileName = $this->getFileName();
		dirname($this->filePath);

		//检查文件大小是否超出限制
		if (!$this->checkSize()) {
			$this->stateInfo = $this->getStateInfo('ERROR_SIZE_EXCEED');
			return;
		}

		//检查是否不允许的文件格式
		if (!$this->checkType()) {
			$this->stateInfo = $this->getStateInfo('ERROR_TYPE_NOT_ALLOWED');
			return;
		}

		$dir = 'images/' . date('y/m/d');
		$path = QiniuBucket::save($uploadFile, $dir);
		if ($path === false) {
			$this->stateInfo = $this->getStateInfo('ERROR_FILE_MOVE');
		} else {
			$this->fullName = cdn($path);
			$this->stateInfo = $this->stateMap[0];
		}
	}

	/**
	 * 处理base64编码的图片上传
	 */
	private function upBase64()
	{
		$base64Data = $_POST[$this->fileField];
		$img = base64_decode($base64Data);

		$this->oriName = $this->config['oriName'];
		$this->fileSize = strlen($img);
		$this->fileType = $this->getFileExt();
		$this->fullName = $this->getFullName();
		$this->filePath = $this->getFilePath();
		$this->fileName = $this->getFileName();
		$dirname = dirname($this->filePath);

		//检查文件大小是否超出限制
		if (!$this->checkSize()) {
			$this->stateInfo = $this->getStateInfo('ERROR_SIZE_EXCEED');
			return;
		}

		//创建目录失败
		if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
			$this->stateInfo = $this->getStateInfo('ERROR_CREATE_DIR');
			return;
		} elseif (!is_writeable($dirname)) {
			$this->stateInfo = $this->getStateInfo('ERROR_DIR_NOT_WRITEABLE');
			return;
		}

		//移动文件
		if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
			$this->stateInfo = $this->getStateInfo('ERROR_WRITE_CONTENT');
		} else { //移动成功
			$this->stateInfo = $this->stateMap[0];
		}
	}

	/**
	 * 拉取远程图片
	 * @return mixed
	 */
	private function saveRemote()
	{
		$imgUrl = htmlspecialchars($this->fileField);
		$imgUrl = str_replace('&amp;', '&', $imgUrl);

		//http开头验证
		if (strpos($imgUrl, 'http') !== 0) {
			$this->stateInfo = $this->getStateInfo('ERROR_HTTP_LINK');
			return;
		}

		preg_match('/(^https*:\/\/[^:\/]+)/', $imgUrl, $matches);
		$host_with_protocol = count($matches) > 1 ? $matches[1] : '';

		// 判断是否是合法 url
		if (!filter_var($host_with_protocol, FILTER_VALIDATE_URL)) {
			$this->stateInfo = $this->getStateInfo('INVALID_URL');
			return;
		}

		preg_match('/^https*:\/\/(.+)/', $host_with_protocol, $matches);
		$host_without_protocol = count($matches) > 1 ? $matches[1] : '';

		// 此时提取出来的可能是 ip 也有可能是域名，先获取 ip
		$ip = gethostbyname($host_without_protocol);
		// 判断是否是私有 ip
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
			$this->stateInfo = $this->getStateInfo('INVALID_IP');
			return;
		}

		//获取请求头并检测死链
		$heads = get_headers($imgUrl, 1);
		if (!(stristr($heads[0], '200') && stristr($heads[0], 'OK'))) {
			$this->stateInfo = $this->getStateInfo('ERROR_DEAD_LINK');
			return;
		}
		//格式验证(扩展名验证和Content-Type验证)
		$fileType = strtolower(strrchr($imgUrl, '.'));
		if (!in_array($fileType, $this->config['allowFiles']) || !isset($heads['Content-Type']) || !stristr($heads['Content-Type'], 'image')) {
			$this->stateInfo = $this->getStateInfo('ERROR_HTTP_CONTENTTYPE');
			return;
		}

		//打开输出缓冲区并获取远程图片
		ob_start();
		$context = stream_context_create(
			array('http' => array(
				'follow_location' => false // don't follow redirects
			))
		);
		readfile($imgUrl, false, $context);
		$img = ob_get_contents();
		ob_end_clean();
		preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/", $imgUrl, $m);

		$this->oriName = $m ? $m[1] : '';
		$this->fileSize = strlen($img);
		$this->fileType = $this->getFileExt();
		$this->fullName = $this->getFullName();
		$this->filePath = $this->getFilePath();
		$this->fileName = $this->getFileName();
		$dirname = dirname($this->filePath);

		//检查文件大小是否超出限制
		if (!$this->checkSize()) {
			$this->stateInfo = $this->getStateInfo('ERROR_SIZE_EXCEED');
			return;
		}

		//创建目录失败
		if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
			$this->stateInfo = $this->getStateInfo('ERROR_CREATE_DIR');
			return;
		} elseif (!is_writeable($dirname)) {
			$this->stateInfo = $this->getStateInfo('ERROR_DIR_NOT_WRITEABLE');
			return;
		}

		//移动文件
		if (!(file_put_contents($this->filePath, $img) && file_exists($this->filePath))) { //移动失败
			$this->stateInfo = $this->getStateInfo('ERROR_WRITE_CONTENT');
		} else { //移动成功
			$this->stateInfo = $this->stateMap[0];
		}
	}

	/**
	 * 上传错误检查
	 * @param $errCode
	 * @return string
	 */
	private function getStateInfo($errCode)
	{
		return !$this->stateMap[$errCode] ? $this->stateMap['ERROR_UNKNOWN'] : $this->stateMap[$errCode];
	}

	/**
	 * 获取文件扩展名
	 * @return string
	 */
	private function getFileExt()
	{
		return strtolower(strrchr($this->oriName, '.'));
	}

	/**
	 * 重命名文件
	 * @return string
	 */
	private function getFullName()
	{
		//替换日期事件
		$t = time();
		$d = explode('-', date('Y-y-m-d-H-i-s'));
		$format = $this->config['pathFormat'];
		$format = str_replace('{yyyy}', $d[0], $format);
		$format = str_replace('{yy}', $d[1], $format);
		$format = str_replace('{mm}', $d[2], $format);
		$format = str_replace('{dd}', $d[3], $format);
		$format = str_replace('{hh}', $d[4], $format);
		$format = str_replace('{ii}', $d[5], $format);
		$format = str_replace('{ss}', $d[6], $format);
		$format = str_replace('{time}', $t, $format);

		//过滤文件名的非法自负,并替换文件名
		$oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
		$oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
		$format = str_replace('{filename}', $oriName, $format);

		//替换随机字符串
		$randNum = rand(1, 10000000000) . rand(1, 10000000000);
		if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
			$format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
		}

		$ext = $this->getFileExt();
		return $format . $ext;
	}

	/**
	 * 获取文件名
	 * @return string
	 */
	private function getFileName()
	{
		return substr($this->filePath, strrpos($this->filePath, '/') + 1);
	}

	/**
	 * 获取文件完整路径
	 * @return string
	 */
	private function getFilePath()
	{
		$fullname = $this->fullName;
		$rootPath = $_SERVER['DOCUMENT_ROOT'];

		if (substr($fullname, 0, 1) != '/') {
			$fullname = '/' . $fullname;
		}

		return $rootPath . $fullname;
	}

	/**
	 * 文件类型检测
	 * @return bool
	 */
	private function checkType()
	{
		return in_array($this->getFileExt(), $this->config['allowFiles']);
	}

	/**
	 * 文件大小检测
	 * @return bool
	 */
	private function checkSize()
	{
		return $this->fileSize <= ($this->config['maxSize']);
	}

	/**
	 * 获取当前上传成功文件的各项信息
	 * @return array
	 */
	public function getFileInfo()
	{
		return array(
			'state' => $this->stateInfo,
			'url' => $this->fullName,
			'title' => $this->fileName,
			'original' => $this->oriName,
			'type' => $this->fileType,
			'size' => $this->fileSize
		);
	}
}
