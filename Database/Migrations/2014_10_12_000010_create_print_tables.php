<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        /**
         * 打印模板
         */
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->integer('creator_id')->index()->comment('创建者');
            $table->integer('parent_id')->default(0)->index()->comment('父级ID');
            $table->string('display_name')->index()->comment('打印模板名称');
            $table->string('name')->index()->comment('打印模板标识');
            $table->json('template')->nullable()->comment('打印模板');
            $table->longText('template_html')->nullable()->comment('打印模板HTML');
            $table->json('business_variables')->nullable()->comment('业务变量');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();
        });

        Schema::create('print_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('department_id')->index()->comment('所属部门');
            $table->integer('creator_id')->index()->comment('创建者');
            $table->integer('print_template_id')->index()->comment('打印模板');
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('print_templates');
        Schema::dropIfExists('print_histories');
    }
};
