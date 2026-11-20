<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePkrsCategoryRequest;
use App\Http\Requests\UpdatePkrsCategoryRequest;
use App\Models\PkrsCategory;

class PkrsCategoryAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:pkrs_categories.view')->only(['index']);
        $this->middleware('permission:pkrs_categories.create')->only(['create', 'store']);
        $this->middleware('permission:pkrs_categories.update')->only(['edit', 'update']);
        $this->middleware('permission:pkrs_categories.delete')->only(['destroy']);
    }

    public function index()
    {
        $categories = PkrsCategory::latest()->paginate(10);

        return view('admin.pkrs-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.pkrs-categories.create');
    }

    public function store(StorePkrsCategoryRequest $request)
    {
        PkrsCategory::create($request->validated());

        return redirect()
            ->route('admin.pkrs-categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(PkrsCategory $pkrs_category)
    {
        return view('admin.pkrs-categories.edit', [
            'category' => $pkrs_category
        ]);
    }

    public function update(
        UpdatePkrsCategoryRequest $request,
        PkrsCategory $pkrs_category
    ) {
        $pkrs_category->update($request->validated());

        return redirect()
            ->route('admin.pkrs-categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(PkrsCategory $pkrs_category)
    {
        $pkrs_category->delete();

        return redirect()
            ->route('admin.pkrs-categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
