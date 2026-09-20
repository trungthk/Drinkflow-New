<?php

declare(strict_types=1);

namespace App\Support\Helpers;

/**
 * Translated messages consumed by the campaign create/edit script (resources/js/admin/campaign-create.js).
 */
class CampaignFormHelper
{
    /**
     * Build the localized messages for the current locale.
     *
     * Placeholders (:count, :url, :message) are left in the strings for the script to fill in.
     *
     * @return array<string, string> Messages keyed by the camelCase names the script reads.
     */
    public static function messages(): array
    {
        return [
            'imageUploadFailed' => __('admin.campaign_form_image_upload_failed'),
            'copySuffix' => __('admin.campaign_form_copy_suffix'),
            'jsonLoaded' => __('admin.campaign_form_json_loaded'),
            'jsonNotArray' => __('admin.campaign_form_json_not_array'),
            'jsonInvalid' => __('admin.campaign_form_json_invalid'),
            'crawlerConnecting' => __('admin.campaign_form_crawler_connecting'),
            'roomUnknown' => __('admin.campaign_form_room_unknown'),
            'crawlerFailed' => __('admin.campaign_form_crawler_failed'),
            'crawlerSuccess' => __('admin.campaign_form_crawler_success'),
            'crawlerFallback' => __('admin.campaign_form_crawler_fallback'),
            'crawlerError' => __('admin.campaign_form_crawler_error'),
            'nameRequired' => __('admin.campaign_form_name_required'),
            'createFailed' => __('admin.campaign_form_create_failed'),
            'updateFailed' => __('admin.campaign_form_update_failed'),
            'genericError' => __('admin.campaign_form_generic_error'),
            'cancelUrlMissing' => __('admin.campaign_form_cancel_url_missing'),
            'cancelFailed' => __('admin.campaign_form_cancel_failed'),
        ];
    }
}
