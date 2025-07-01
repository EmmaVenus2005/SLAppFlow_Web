<?php

/**
 * Contextual functions used during flow execution
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
 * Pay-specific additional parameters are accessible using:
 *
 * - AFGetFlowSession()         → UUID of the avatar who paid the object
 * - AFGetFlowParameter(index)  → Indexed array of payment data:
 *      [0] = payerName            (string)
 *      [1] = amount               (string)
 *      [2] = internalCount        (integer)
 * 
 */


 SLOwnerSay(AFGetFlowObjectID(), "on_payment.php: Flow triggered by payment from " . AFGetFlowSession() . " (" . AFGetFlowParameter(0) . ") : " . AFGetFlowParameter(1) . " L$");
 SLOwnerSay(AFGetFlowObjectID(), "on_payment.php: Internal count: " . AFGetFlowParameter(2));