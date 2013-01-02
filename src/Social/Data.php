<?php

namespace Social;

/**
 * A reprensentation of data returned from a social API.
 */
interface Data
{
    /**
     * Update with newly fetched data.
     * 
     * @param object|Data $data
     * @return Data
     */
    public function setData($data);
}
