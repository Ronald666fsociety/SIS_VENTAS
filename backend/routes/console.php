<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('pedidos:cancelar-vencidos')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
