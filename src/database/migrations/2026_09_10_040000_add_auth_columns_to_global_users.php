<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { if(!Schema::hasColumn('global_users','remember_token')) Schema::table('global_users',fn(Blueprint $t)=>$t->rememberToken()); } public function down(): void { if(Schema::hasColumn('global_users','remember_token')) Schema::table('global_users',fn(Blueprint $t)=>$t->dropColumn('remember_token')); } };
