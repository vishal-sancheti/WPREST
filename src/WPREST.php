<?php
namespace Tradzero\WPREST;

use GuzzleHttp\Client;
use Tradzero\WPREST\Resources\Post;
use Tradzero\WPREST\Resources\Category;
use Tradzero\WPREST\Resources\Tag;

class WPREST
{
    protected $client;

    protected $authenticateUrl = 'jwt-auth/v1/token';
    protected $listPostsUrl = 'wp/v2/posts';
    protected $listPagesUrl = 'wp/v2/pages';
    protected $listCategoriesUrl = 'wp/v2/categories';
    protected $listTagsUrl = 'wp/v2/tags';
    protected $createMediaUrl   = 'wp/v2/media';
    protected $createPostUrl   = 'wp/v2/posts';
    protected $updatePostUrl   = 'wp/v2/posts/{id}';
    protected $createCategoriesUrl = 'wp/v2/categories';
    protected $createTagsUrl = 'wp/v2/tags';

    public function __construct()
    {
        $token = $this->getToken();

        $this->client = new Client(['headers' => [
            'Authorization' => "Bearer $token"
        ]]);
    }

    public function listPosts(){
        $listPostsUrl = $this->getFullUrl($this->listPostsUrl);
        $response = $this->client->get($listPostsUrl);

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 200) {
            return $result;
        } else {
            return null;
        }
    }

    public function listPages(){
        $listPagesUrl = $this->getFullUrl($this->listPagesUrl);
        $response = $this->client->get($listPagesUrl);

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 200) {
            return $result;
        } else {
            return null;
        }
    }

    public function listCategories(){
        $listCategoriesUrl = $this->getFullUrl($this->listCategoriesUrl);
        $response = $this->client->get($listCategoriesUrl);

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 200) {
            return $result;
        } else {
            return null;
        }
    }

    public function listTags(){
        $listTagsUrl = $this->getFullUrl($this->listTagsUrl);
        $response = $this->client->get($listTagsUrl);

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 200) {
            return $result;
        } else {
            return null;
        }
    }

    public function createMedia($filename,$path)
    {
        $fdata = file_get_contents($path);

        $headers = [
            "Content-Disposition" => "form-data; filename=$filename",
            'Content-Type' => mime_content_type($path),
        ];


        $createMediaUrl = $this->getFullUrl($this->createMediaUrl);


        $response = $this->client->post($createMediaUrl, [
                'headers' => $headers,
                'body' => $fdata,
            ]
        );

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 201) {
            return $result;
        } else {
            return null;
        }
    }

    public function createPost(Post $post)
    {
        $createPostUrl = $this->getFullUrl($this->createPostUrl);

        $response = $this->client->post($createPostUrl, ['json' => $post]);

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 201) {
            return $result;
        } else {
            return null;
        }
    }

    public function updatePost(Post $post)
    {
        $postId = $post->getId();

        $baseUrl = $this->getFullUrl($this->updatePostUrl);

        $updatePostUrl = str_replace('{id}', $postId, $baseUrl);

        $response = $this->client->post($updatePostUrl, ['json' => $post]);

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 204) {
            return $result;
        } else {
            return null;
        }
    }

    public function findCategoryOrCreate(Category $category)
    {
        $listCategoriesUrl = $this->getFullUrl($this->listCategoriesUrl);

        $queryFilter = [
            'slug' => $category->getSlug(),
        ];

        $response = $this->client->get($listCategoriesUrl, ['query' => $queryFilter]);

        $result = json_decode($response->getBody());

        if ($result) {
            if ($result[0]->name == htmlspecialchars($category->getName())) {
                return Category::build($result[0]);
            }
        }
        return $this->createCategory($category);
    }

    public function createCategory(Category $category)
    {
        $createCategoriesUrl = $this->getFullUrl($this->createCategoriesUrl);

        $response = $this->client->post($createCategoriesUrl, ['json' => $category]);

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 201) {
            return Category::build($result);
        } else {
            return null;
        }
    }

    public function findTagOrCreate(Tag $tag)
    {
        $listTagsUrl = $this->getFullUrl($this->listTagsUrl);

        $queryFilter = [
            'slug' => $tag->getSlug(),
        ];

        $response = $this->client->get($listTagsUrl, ['query' => $queryFilter]);

        $result = json_decode($response->getBody());

        if ($result) {
            if ($result[0]->name == htmlspecialchars($tag->getName())) {
                return Tag::build($result[0]);
            }
        }
        return $this->createTag($tag);
    }


    public function createTag(Tag $tag)
    {
        $createTagsUrl = $this->getFullUrl($this->createTagsUrl);

        $response = $this->client->post($createTagsUrl, ['json' => $tag]);

        $result = json_decode($response->getBody());

        if ($response->getStatusCode() == 201) {
            return Tag::build($result);
        } else {
            return null;
        }
    }

    protected function getToken()
    {
        // because of guzzle 6 change client to immutable
        $templateClient = new Client();

        $credentials = [
            'username' => config('wordpress.account.username'),
            'password' => config('wordpress.account.password'),
        ];

        $authenticateUrl = $this->getFullUrl($this->authenticateUrl);

        $response = $templateClient->post($authenticateUrl, ['form_params' => $credentials]);

        $result = json_decode($response->getBody(), true);

        return $result['token'];
    }

    private function getFullUrl($url)
    {
        $baseUrl = config('wordpress.endpoint');

        return $baseUrl . $url;
    }
}
