<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Repositories\StoreRepository;

class StoreController extends Controller
{
    protected $repo = null;

    /**
     * Constructor
     */
    public function __construct(StoreRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * create a store branch
     *
     * @return Response
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $store = $this->repo->create($request->get('name'));

        return response()->json($store);
    }

    /**
     * update a store branch
     *
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $store = $this->repo->find($id);

        if (!$store) {
            abort(404);
        }

        $ret = $this->repo->update($request->get('name'), $id);

        return response()->json(['success' => $ret]);
    }

    /**
     * delete a store branch
     *
     * @return Response
     */
    public function delete(Request $request, $id)
    {
        $ret = $this->repo->delete($id);

        return response()->json(['success' => $ret]);
    }

    /**
     * move a store branch to a different parent
     *
     * @return Response
     */
    public function move(Request $request, $id, $parentId)
    {
        $ret = $this->repo->changeParent($id, $parentId);

        return response()->json(['success' => $ret]);
    }

    /**
     * view all store branches with its children
     *
     * @return Response
     */
    public function index()
    {
        $stores = $this->repo->all();

        return response()->json($stores);
    }

    /**
     * view one store branch
     *
     * @return Response
     */
    public function show(Request $request, $id)
    {
        $withChildren = $request->get('with-children', false);

        $store = $withChildren
            ? $this->repo->findWithChildren($id)
            : $this->repo->find($id);

        return response()->json($store);
    }
}
