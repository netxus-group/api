<?php

namespace App\Controllers;

abstract class PortalBaseApiController extends BaseApiController
{
    protected function portalAuth(): ?object
    {
        return $this->request->portalAuth ?? null;
    }

    protected function portalUserId(): ?string
    {
        return $this->portalAuth()?->portalUserId;
    }
}
