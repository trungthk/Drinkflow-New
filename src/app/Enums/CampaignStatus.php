<?php
namespace App\Enums;
enum CampaignStatus: string { case Draft='draft'; case Scheduled='scheduled'; case Active='active'; case Closing='closing'; case Closed='closed'; case Cancelled='cancelled'; case Archived='archived'; }
