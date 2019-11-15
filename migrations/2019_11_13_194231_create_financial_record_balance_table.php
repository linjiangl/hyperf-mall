<?php

declare(strict_types=1);
/**
 * Multi-user mall
 *
 * @link     https://www.doubi.site
 * @document https://doc.doubi.site
 * @contact  8257796@qq.com
 */

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateFinancialRecordBalanceTable extends Migration
{
    protected $table = 'financial_record_balance';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->integer('user_id', false, true);
            $table->string('type', 30)->comment('类型 recharged:充值 consumed:消费');
            $table->decimal('fee', 9, 2)->default(0);
            $table->string('remark', 255);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id']);
            $table->index(['fee']);
            $table->index(['user_id', 'fee']);
            $table->index(['created_at']);
        });

        \Hyperf\DbConnection\Db::statement("ALTER TABLE `{$this->table}` COMMENT '财务流水记录-余额'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
}
