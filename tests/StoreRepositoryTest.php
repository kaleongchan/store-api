<?php

use App\Repositories\StoreRepository;
use Illuminate\Support\Facades\Artisan;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class StoreRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->repo = app(StoreRepository::class);

        Artisan::call('db:seed');
    }

    /**
     * Test find
     */
    public function testFind()
    {
        $id = DB::table('stores')->min('id');
        $store = $this->repo->find($id);
        $this->assertNotNull($store);

        // children property is not attahed
        $this->assertFalse(isset($store->children));
    }

    /**
     * Test find one store with children
     *
     * @return void
     */
    public function testFindWithChildren()
    {
        // grab the big tree id
        $id = DB::table('stores')->orderBy('rgt', 'desc')->limit(1)->pluck('id')[0];

        $store = $this->repo->findWithChildren($id);

        $this->assertNotNull($store);
        $this->assertTrue(isset($store->children));
        $this->assertCount(3, $store->children);
    }

    /**
     * Test find all stores
     *
     * @return void
     */
    public function testFindAll()
    {
        $stores = $this->repo->all();

        $this->assertCount(2, $stores);
    }

    /**
     * Test create one store
     *
     * @return void
     */
    public function testCreateOne()
    {
        $id1 = $this->repo->create('store1');
        $store1 = $this->repo->find($id1);
        $this->assertNotNull($store1);
        $this->assertEquals($store1->rgt - $store1->lft, 1);
    }

    /**
     * Test create two stores
     *
     * @return void
     */
    public function testCreateTwo()
    {
        $id1 = $this->repo->create('store1');
        $id2 = $this->repo->create('store2');

        $store1 = $this->repo->find($id1);
        $store2 = $this->repo->find($id2);

        $this->assertNotNull($store1);
        $this->assertNotNull($store2);

        // check that store 2 is inserted to the next right
        $this->assertEquals($store1->rgt + 1, $store2->lft);
    }

    /**
     * Test change parent
     *
     * @return void
     */
    public function testChangeParentSingle()
    {
        $id1 = $this->repo->create('store1');
        $id2 = $this->repo->create('store2');

        $this->repo->changeParent($id2, $id1);

        $store1 = $this->repo->find($id1);
        $store2 = $this->repo->find($id2);

        $this->assertNotNull($store1);
        $this->assertNotNull($store2);

        $this->assertEquals($store1->lft + 1, $store2->lft);
        $this->assertEquals($store1->rgt - 1, $store2->rgt);
    }

    /**
     * Test change parent of a store with children
     *
     * @return void
     */
    public function testChangeParentWithChildren()
    {
        // grab the big tree id
        $id1 = DB::table('stores')->orderBy('rgt', 'desc')->limit(1)->pluck('id')[0];
        $id2 = $this->repo->create('parent');

        $this->repo->changeParent($id1, $id2);

        $store1 = $this->repo->find($id1);
        $store2 = $this->repo->find($id2);

        $this->assertEquals($store2->lft + 1, $store1->lft);
        $this->assertEquals($store2->rgt - 1, $store1->rgt);

    }

    /**
     * Test update a store name
     *
     * @return void
     */
    public function testUpdate()
    {
        $id = $this->repo->create('store1');
        $store = $this->repo->find($id);
        $this->assertEquals($store->name, 'store1');

        $this->repo->update('new store1', $id);
        $store = $this->repo->find($id);
        $this->assertEquals($store->name, 'new store1');
    }

    /**
     * Test delete a store
     *
     * @return void
     */
    public function testDeleteOne()
    {
        $id = $this->repo->create('store1');
        $store = $this->repo->find($id);
        $this->assertNotNull($store);

        $this->repo->delete($id);
        $store = $this->repo->find($id);
        $this->assertNull($store);
    }


    /**
     * Test delete a store with all its children
     *
     * @return void
     */
    public function testDeleteTree()
    {
        // grab the big tree id
        $id = DB::table('stores')->orderBy('rgt', 'desc')->limit(1)->pluck('id')[0];
        $store_bk = $this->repo->find($id);
        $exists = DB::table('stores')->where('lft', '>=', $store_bk->lft)
            ->where('rgt', '<=', $store_bk->rgt)
            ->exists();
        $this->assertTrue($exists);

        $this->repo->delete($id);
        $store = $this->repo->find($id);
        $this->assertNull($store);


        $exists = DB::table('stores')->where('lft', '>=', $store_bk->lft)
            ->where('rgt', '<=', $store_bk->rgt)
            ->exists();
        $this->assertFalse($exists);
    }
}
