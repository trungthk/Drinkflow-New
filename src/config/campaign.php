<?php

return [
    // Minutes before a campaign's ordering deadline when members who have not ordered yet (and the room admins)
    // are reminded. 0 turns the reminder off.
    'deadline_reminder_minutes' => max(0, (int) env('CAMPAIGN_DEADLINE_REMINDER_MINUTES', 15)),
];
