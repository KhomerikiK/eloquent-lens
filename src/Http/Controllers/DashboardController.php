<?php

namespace EloquentLens\Http\Controllers;

use EloquentLens\Services\ModelParser;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __construct(
        protected ModelParser $parser
    ) {}

    public function index()
    {
        return view('eloquent-lens::dashboard', [
            'apiUrl' => route('eloquent-lens.api.models'),
        ]);
    }

    public function models()
    {
        return response()->json($this->parser->parse());
    }
}
