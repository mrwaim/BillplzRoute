<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBillplzEnumOnOrders extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE orders CHANGE payment_mode payment_mode ENUM('COD', 'BankTransfer', 'Billplz')");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE orders CHANGE payment_mode payment_mode ENUM('COD', 'BankTransfer')");
	}
}
