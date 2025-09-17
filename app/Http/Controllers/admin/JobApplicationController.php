<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    //
    public function index()
    {
        $applications = JobApplication::orderBy('created_at', 'DESC')
            ->with('job', 'user', 'employer')
            ->paginate(5);

        return view('admin.job-applications.list', ['applications' => $applications]);
    }

    // DESTROY 
    public function destroy(Request $request)
    {
        $id = $request->id;

        $application = JobApplication::find($id);

        if ($application == null) {
            session()->flash('error', 'Either Job Application Deleted Or Not Found.');

            return response()->json([
                'status' => false
            ]);
        }

        $application->delete();
        session()->flash('success', 'Job Application Deleted Successfully.');

        return response()->json([
            'status' => false
        ]);
    }
}
