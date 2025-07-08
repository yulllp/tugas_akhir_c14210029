<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $minDp = Setting::get('min_dp_percent', 20);
        $x = Setting::get('credit_reminder_days', 7);
        return view('setting.edit', compact('minDp', 'x'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'min_dp_percent'       => 'required|integer|min:0|max:100',
            'credit_reminder_days' => 'required|integer|min:1',
        ]);
        Setting::set('credit_reminder_days', $request->credit_reminder_days);
        Setting::set('min_dp_percent', $request->min_dp_percent);
        return back()->with('success', 'Setting berhasil disimpan.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        //
    }
}
