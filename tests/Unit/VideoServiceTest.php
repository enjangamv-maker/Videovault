<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Exceptions\NotFoundException;
use App\Services\VideoService;
use Tests\TestCase;

final class VideoServiceTest extends TestCase
{
    public function testCreateVideo(): void
    {
        $vs = new VideoService();
        $userId = $this->createUser();
        $catId = $this->createCategory();
        $id = $vs->create([
            'title' => 'My Video',
            'description' => 'Desc',
            'category_id' => $catId,
            'video_file' => 'test/v.mp4',
            'duration' => 300,
            'uploaded_by' => $userId,
        ]);
        $this->assertGreaterThan(0, $id);
        $this->assertEquals('My Video', $vs->find($id)['title']);
    }

    public function testFindThrowsNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        (new VideoService())->find(999999);
    }

    public function testUpdateVideo(): void
    {
        $vs = new VideoService();
        $id = $this->createVideo(['title' => 'Original']);
        $vs->update($id, ['title' => 'Updated', 'description' => 'New desc']);
        $video = $vs->find($id);
        $this->assertEquals('Updated', $video['title']);
        $this->assertEquals('New desc', $video['description']);
    }

    public function testDeleteVideo(): void
    {
        $vs = new VideoService();
        $id = $this->createVideo();
        $vs->delete($id);
        $this->expectException(NotFoundException::class);
        $vs->find($id);
    }

    public function testPaginateReturnsCorrectPages(): void
    {
        $vs = new VideoService();
        $userId = $this->createUser();
        $catId = $this->createCategory();
        for ($i = 1; $i <= 15; $i++) {
            $this->createVideo(['title' => "Video $i", 'category_id' => $catId, 'uploaded_by' => $userId]);
        }
        $r1 = $vs->paginate(null, null, 1, 10);
        $this->assertEquals(15, $r1['total']);
        $this->assertEquals(2, $r1['pages']);
        $this->assertCount(10, $r1['items']);
        $r2 = $vs->paginate(null, null, 2, 10);
        $this->assertCount(5, $r2['items']);
    }

    public function testSearchByKeyword(): void
    {
        $vs = new VideoService();
        $userId = $this->createUser();
        $catId = $this->createCategory();
        $this->createVideo(['title' => 'Belajar PHP Dasar', 'category_id' => $catId, 'uploaded_by' => $userId]);
        $this->createVideo(['title' => 'Belajar JavaScript', 'category_id' => $catId, 'uploaded_by' => $userId]);
        $this->createVideo(['title' => 'Memasak Nasi Goreng', 'category_id' => $catId, 'uploaded_by' => $userId]);
        $this->assertEquals(2, $vs->paginate('Belajar', null, 1, 10)['total']);
    }

    public function testFilterByCategory(): void
    {
        $vs = new VideoService();
        $userId = $this->createUser();
        $cat1 = $this->createCategory('CatA');
        $cat2 = $this->createCategory('CatB');
        $this->createVideo(['category_id' => $cat1, 'uploaded_by' => $userId]);
        $this->createVideo(['category_id' => $cat1, 'uploaded_by' => $userId]);
        $this->createVideo(['category_id' => $cat2, 'uploaded_by' => $userId]);
        $this->assertEquals(2, $vs->paginate(null, $cat1, 1, 10)['total']);
    }

    public function testStatsReturnsAllCounts(): void
    {
        $vs = new VideoService();
        $userId = $this->createUser();
        $catId = $this->createCategory();
        $this->createVideo(['category_id' => $catId, 'uploaded_by' => $userId]);
        $this->createVideo(['category_id' => $catId, 'uploaded_by' => $userId]);
        $stats = $vs->stats();
        $this->assertEquals(2, $stats['videos']);
        $this->assertEquals(1, $stats['users']);
        $this->assertEquals(1, $stats['categories']);
    }
}
