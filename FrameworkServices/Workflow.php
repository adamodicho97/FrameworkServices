<?php

namespace FrameworkServices;

class Workflow extends BaseServices
{

    /**
     * Generate URL
     * 
     * @param (int) $userId
     * @param (int) $workflowId
     * 
     * @return (string)
     */
    public function generateHook($workflowId, $userId) : string
    {
        return 'https://hook.myframework.app/' . $userId . '/' . $workflowId;
    }
}

?>