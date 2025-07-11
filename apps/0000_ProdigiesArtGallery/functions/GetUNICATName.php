<?php

function GetUNICATName($number)
{

    // Only relevant for the Global user
    if (AFGetOwnerID() !== "Global") return false;

    // Gets the list elements
    $info = NVGetList("Information", $number);

    // Returns the name
    return json_decode($info, true)['name'];

}