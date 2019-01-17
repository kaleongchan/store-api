<?php

namespace App\Repositories;

use stdClass;
use Exception;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Query\Builder;

class StoreRepository
{
    protected $table = 'stores';

    /**
     * Return a new query builder of this table
     *
     * @param string $alias
     * @return Builder
     */
    protected function query($alias = '')
    {
        if ($alias) {
            $alias = " AS {$alias}";
        }

        return DB::table($this->table . $alias);
    }

    /**
     * Create a new store
     *
     * @param string $name
     * @return int|null
     */
    public function create(string $name)
    {
        $retId = null;

        DB::transaction(function() use (&$retId, $name) {

            // find the right-most number and insert to the next right
            $maxRight = $this->query()->max('rgt') ?: -1;
            $left = $maxRight + 1;

            $data = [
                'name' => $name,
                'lft' => $left,
                'rgt' => $left + 1
            ];

            $retId = $this->query()->insertGetId($data);
        });

        return $retId;
    }

    /**
     * Change a store to be under a parent store
     *
     * @param int $id
     * @param int $parentId
     * @return void
     * @throws Exception
     */
    public function changeParent($id, $parentId)
    {
        DB::transaction(function() use ($id, $parentId) {

            $store = $this->find($id);
            $parent = $this->find($parentId);

            if (!$store || !$parent) {
                abort(404);
            }

            // size of the store occupies
            $size = $store->rgt - $store->lft + 1;

            $this->query()
                ->where('lft', '>', $parent->rgt)
                ->update([
                    'lft' => DB::raw("lft + ({$size})"),
                    'rgt' => DB::raw("rgt + ({$size})")
                ]);

            $this->query()
                ->where('id', $parentId)
                ->update([
                    'rgt' => DB::raw("rgt + ({$size})")
                ]);

            $store = $this->find($id);
            $delta = $store->lft - $parent->rgt;

            // find the IDs of the target store and its children
            $storeIds = DB::select("SELECT s.id FROM {$this->table} AS s, {$this->table} AS parent WHERE s.lft BETWEEN parent.lft AND parent.rgt AND parent.id=:id", ['id' => $id]);
            $storeIds = array_pluck($storeIds, 'id');
            $this->query()
                ->whereIn('id', $storeIds)
                ->update([
                    'lft' => DB::raw("lft - ({$delta})"),
                    'rgt' => DB::raw("rgt - ({$delta})")
                ]);

        });

        return true;

    }

    /**
     * Update a store name
     *
     * @param string $name
     * @param int    $id
     * @return int
     */
    public function update($name, $id)
    {
        return $this->query()->where('id', $id)->update(['name' => $name]);
    }

    /**
     * Find a store by its id
     *
     * @param int $id
     * @return stdClass
     */
    public function find($id)
    {
        return $this->query()
            ->where('id', $id)
            ->first();
    }

    /**
     * Find store with children hierarchy
     *
     * @param mixed $id
     * @return array|stdClass|null
     */
    public function findWithChildren($id = null)
    {
        if ($id) {
            $subQuery = $this->query('s')
                ->joinSub($this->query()->where('id', $id), 'p', function($join) {
                    $join->whereRaw('s.lft BETWEEN p.lft AND p.rgt');
                })
                ->select('s.*');
        } else {
            $subQuery = $this->query();
        }

        $data = $this->query('s')
            ->joinSub($subQuery, 'p', function($join) {
                $join->whereRaw('s.lft BETWEEN p.lft AND p.rgt');
            })
            ->addSelect('s.*')
            ->selectRaw('(COUNT(p.id) - 1) AS depth')
            ->groupBy('s.id')
            ->orderBy('s.lft')
            ->get()
            ->all();

        $trees = $this->buildTrees($data);


        if ($id) {
            if (empty($trees)) {
                return null;
            }

            return $trees[0];
        }

        return $trees;
    }

    /**
     * Build store list into tree(s), stores must be sorted by lft
     *
     * @param array $stores
     * @param array
     */
    public function buildTrees(array $stores)
    {
        if (empty($stores)) {
            return [];
        }

        $depth = -1;
        $stack = [
            $depth => (object)['children'=> []]
        ];

        foreach($stores as $store) {
            $parent = $stack[$store->depth - 1];
            if (!isset($parent->children)) {
                $parent->children = [];
            }

            $parent->children[] = $store;

            $stack[$store->depth] = $store;

            $depth = $store->depth;
        }

        return $stack[-1]->children;
    }

    /**
     * Delete a store with all its children
     *
     * @param int $id
     * @return int
     */
    public function delete($id)
    {
        $ids = $this->query('s')
            ->join($this->table . ' AS p', function($join) use ($id) {
                $join->whereRaw('s.lft BETWEEN p.lft and p.rgt')
                    ->where('p.id', $id);
            })
            ->select('s.id')
            ->pluck('id')
            ->all();

        return $this->query()
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * Find all stores with children
     *
     * @return array
     */
    public function all()
    {
        return $this->findWithChildren(null);
    }
}