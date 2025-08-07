<?php

namespace App\Http\Controllers\Back\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Back\Catalog\Options;
use Illuminate\Http\Request;

class OptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('search') && ! empty($request->search)) {
            $options = Options::query()->groupBy('grupa')->paginate(12);
        } else {
            $options = Options::query()->groupBy('grupa')->paginate(12);
        }

        return view('back.catalog.options.index', compact('options'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.catalog.options.edit');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $option = new Options();

        $stored = $option->validateRequest($request)->create();

        if ($stored) {
            return redirect()->route('options.edit', ['options' => $stored ])->with(['success' => 'Option was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the option.']);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param Author $author
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Options $options)
    {
        return view('back.catalog.options.edit', compact('options'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Options                  $options
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Options $options)
    {

        $updated = $options->validateRequest($request)->edit();

        if ($updated) {
            return redirect()->route('options.edit', ['options' => $options->id])->with(['success' => 'Options was succesfully saved!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error saving the option.']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Options $options)
    {
        $destroyed = Options::destroy($options->id);

        if ($destroyed) {
            return redirect()->route('options')->with(['success' => 'Option was succesfully deleted!']);
        }

        return redirect()->back()->with(['error' => 'Whoops..! There was an error deleting the option.']);
    }
}
