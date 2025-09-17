<?php

namespace App\Http\Controllers;

use App\Mail\JobNotificationEmail;
use App\Models\Category;
use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\SavedJob;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class JobsController extends Controller
{
    // this method will show halaman jobs 
    public function index(Request $request)
    {
        $categories = Category::where('status', 1)->get();
        $jobTypes = JobType::where('status', 1)->get();

        $jobs = Job::where('status', 1);

        // search use keyword
        if (!empty($request->keyword)) {
            $jobs = $jobs->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                $query->orWhere('keywords', 'like', '%' . $request->keyword . '%');
            });
        }

        // search use location
        if (!empty($request->location)) {
            // where = menambah kondisi query AND
            $jobs = $jobs->where('location', $request->location);
        }

        // search use CATEGORY
        if (!empty($request->category)) {
            // where = menambah kondisi query AND
            $jobs = $jobs->where('category_id', $request->category);
        }

        $jobTypeArray = [];
        // search use CATEGORY
        if (!empty($request->jobType)) {
            // [1,2,3]
            $jobTypeArray = explode(',', $request->jobType);

            $jobs = $jobs->whereIn('job_type_id', $jobTypeArray);
        }

        // search use experience
        if (!empty($request->experience)) {
            $jobs = $jobs->where('experience', $request->experience);
        }

        $jobs = $jobs->with(['jobType', 'category']);

        // SORT
        if ($request->sort == '0') {
            $jobs = $jobs->orderBy('created_at', 'ASC');
        } else {
            $jobs = $jobs->orderBy('created_at', 'DESC');
        }

        // paginate
        $jobs = $jobs->paginate(9);

        return view('front.jobs', ['categories' => $categories, 'jobTypes' => $jobTypes, 'jobs' => $jobs, 'jobTypeArray' => $jobTypeArray]);
    }

    // this method will show halaman detail
    public function detail($id)
    {
        // DATA TABLE JOB
        $job = Job::where(['status' => 1, 'id' => $id])->with(['jobType', 'category'])->first();
        // dd($job);

        if ($job == null) {
            abort(404);
        }

        $countSaveJob = 0;

        if (Auth::user()) {
            $countSaveJob = SavedJob::where([
                'user_id' => Auth::user()->id,
                'job_id' => $id,
            ])->count();
        }

        // USER YANG UDAH MELAMAR/APPLY
        $applications = JobApplication::where('job_id', $id)->with('user')->get();
        // dd($applications);

        // dd(Auth::user()->id);

        return view('front.jobDetail', ['job' => $job, 'countSaveJob' => $countSaveJob, 'applications' => $applications]);
    }

    // SAVE JOB
    public function saveJob(Request $request)
    {
        $id = $request->id;

        $job = Job::find($id);

        // Cek apakah Job itu ada di table
        if ($job == null) {
            session()->flash('error', 'Job Not Found.');

            return response()->json([
                'status' => false,
            ]);
        }

        // cek if user already saved job
        $countSaveJob = SavedJob::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id,
        ])->count();

        if ($countSaveJob > 0) {
            session()->flash('error', 'You Already Saved On This Job.');

            return response()->json([
                'status' => false,
            ]);
        }

        // SAVED JOB
        $savedJob = new SavedJob();
        $savedJob->job_id = $id;
        $savedJob->user_id = Auth::user()->id;
        $savedJob->save();


        session()->flash('success', 'You Have Successfully Saved The Job.');

        return response()->json([
            'status' => true,
        ]);
    }

    // APPLY JOB
    public function applyJob(Request $request)
    {
        $id = $request->id;

        $job = Job::where('id', $id)->first();

        // jika job tidak ditemukan di db
        if ($job == null) {
            $message = 'Job Does Not Exist.';
            session()->flash('error', $message);

            return response()->json([
                'status' => false,
                'message' => $message
            ]);
        }

        // you can not apply on your own job
        // jika di table jobs ada users_id yang sama maka jangan di apply
        $employer_id = $job->user_id;

        if ($employer_id == Auth::user()->id) {
            $message = 'You Can Not Apply On Your Own Job.';
            session()->flash('error', $message);

            return response()->json([
                'status' => false,
                'message' => $message
            ]);
        }

        // you can not apply on a job twise / gaboleh apply job yang sama
        $jobApplicationCount = JobApplication::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id
        ])->count();

        if ($jobApplicationCount > 0) {
            $message = 'You AlReady Applied On This Job.';
            session()->flash('error', $message);

            return response()->json([
                'status' => false,
                'message' => $message
            ]);
        }

        $application = new JobApplication();
        $application->job_id = $id;
        $application->user_id = Auth::user()->id;
        $application->employer_id = $employer_id;
        $application->applied_date = now();
        $application->save();

        // send notification email to employer
        $employer = User::where('id', $employer_id)->first();
        $mailData = [
            'employer' => $employer,
            'user' => Auth::user(),
            'job' => $job,
        ];

        Mail::to($employer->email)->send(new JobNotificationEmail($mailData));

        $message = 'You Have Successfully Applied Job.';
        session()->flash('success', $message);

        return response()->json([
            'status' => true,
            'message' => $message
        ]);
    }
}
