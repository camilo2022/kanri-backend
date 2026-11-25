<?php

use App\Mcp\Servers\KanriServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', KanriServer::class)->middleware(['auth:mcp', 'throttle:mcp']);
