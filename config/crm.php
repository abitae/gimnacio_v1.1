<?php

return [
    'automation_user_id' => env('CRM_AUTOMATION_USER_ID'),

    'pipeline' => [
        'enforce_transitions' => env('CRM_ENFORCE_PIPELINE_TRANSITIONS', true),
        'allow_one_step_back' => true,
    ],

    'conversion' => [
        'require_qualified_stage' => env('CRM_REQUIRE_QUALIFIED_CONVERSION', true),
        'min_stage_orden' => (int) env('CRM_CONVERSION_MIN_STAGE_ORDEN', 5),
    ],
];
