<?php
namespace App\Enums;
enum DebtStatus: string { case Unpaid='unpaid'; case Partial='partial'; case Paid='paid'; case Waived='waived'; }
