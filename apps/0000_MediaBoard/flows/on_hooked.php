<?php

/**
 * Contextual variables used during flow execution
 * -----------------------------------------------
 *
 * - AFGetAppID()               → Application identifier (unique per app instance)
 * - AFGetOwnerID()             → UUID of the avatar who owns the object
 * - AFGetOwnerName()           → Display name of the object owner
 * - AFGetFlowAppMode()         → Application mode (to distinguish objects of the same app)
 * - AFGetFlowObjectID()        → UUID of the object that triggered the flow
 * - AFGetFlowObjectName()      → Display name of the object that triggered the flow
 * - AFGetFlowGatewayVersion()  → Version of the gateway (as a float)
 * - AFGetFlowObjectPosition()  → Position (vector) of the object in the region
 * - AFGetFlowObjectRotation()  → Rotation (quaternion) of the object in the region
 * - AFGetFlowRegionPosition()  → Position (vector) of the region in the world
 * - AFGetFlowRegionName()      → Name of the region in the world
 * 
 * Specific functions for on_hooked event :
 * 
 * - AFGetFlowSession()   Not relevant in this case
 * 
 */

SLOwnerSay(AFGetFlowObjectID(), "MediaBoard hooked!");

// JSON structure to apply the media texture to the prim
$textureInfo = [[
    "link" => 1,                    // Target prim (1 = specific prim, not root)
    "face" => 5,                    // Target face on the prim
    "type" => "media",              // Type: media (web URL)
    "scale" => [0.28, 0.43],          // Default scale
    "offset" => [0.0, 0.0],         // Default offset
    "rotation" => 0.0,              // No rotation

    "media" => [
        "url" => WCGetURL() . "/webcontrol/wcmedia.php?board_id=" . AFGetFlowObjectID() . "&owner_id=" . AFGetOwnerID() . "&app=" . AFGetAppID(),
        "width" => 1024,            // Optional: adjust if known
        "height" => 768,            // Optional: adjust if known
        "auto_play" => 1,           // Media auto-start
        "auto_scale" => 1,          // Disable auto-scale (respects PRIM_TEXTURE scaling)
        "whitelist" => [
            WCGetURL()
        ],
        "interact" => "none",       // Who can click/interact ('none', 'anyone'...)
        "control" => "none"         // Who can control (navigate, reload)
    ]
]];

// Applies the received texture to the board
SLApplyTexture(AFGetFlowObjectID(), $textureInfo);

// Registers the board in the database if not existing (only timestamp), 
// or updates the timestamp if existing
$existing = NVGetList("Board", AFGetFlowObjectID());
$existing["last_hooked"] = time();
NVSetList("Board", AFGetFlowObjectID(), json_encode($existing, JSON_UNESCAPED_UNICODE));