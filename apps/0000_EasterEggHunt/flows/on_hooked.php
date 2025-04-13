<?php

/**
 * Contextual variables used during flow execution
 * -----------------------------------------------
 * These variables are automatically populated when a flow is triggered,
 * especially from a touch interaction in Second Life.
 *
 * $appid        string   Application identifier (unique per app instance)
 * $appmode      string   Application mode (optional, to distinguish multiple objects from a same app)
 * $uuid         string   UUID of the avatar who owns the object (object owner)
 * $name         string   Display name of the object owner (avatar name)
 * $objid        string   UUID of the object that triggered the flow
 * $objregion    string   In which region the object is located
 * $objx[y,z]    float    Position x, y and z of the object
 * $objrx[y,z,w] float    Rotation quaternion components of the object
 * 
 * Specific variables for on_hooked event :
 * 
 * $session      string   Not relevant in this case
 * 
 */

if ($appmode === "Egg")
{

    // The egg registers itself in the database
    NVSetSessionList($objregion, "EasterEgg", $objid, "EggName|EggPosition");

    SLOwnerSay($objid, "The egg has been added to the game !");

}