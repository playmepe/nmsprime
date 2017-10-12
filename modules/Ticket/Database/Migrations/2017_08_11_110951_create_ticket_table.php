<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketTable extends BaseMigration {

    protected $tablename = "ticket";

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tablename, function(Blueprint $table)
        {
            $this->up_table_generic($table);

            $table->integer('user_id');
			$table->integer('priority');
            $table->integer('type');
            $table->integer('state');
			$table->string('name');
			$table->text('description');

            return parent::up();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop($this->tablename);
    }

}