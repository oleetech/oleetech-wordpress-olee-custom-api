## Post API Endpoints

### 1. Create Post

**URL:** `/wp-json/custom/v1/post`  
**Method:** `POST`  
**Description:** Creates a new blog post.

**Headers:**
- `Content-Type: multipart/form-data`
- **Authorization:** Bearer YOUR_JWT_TOKEN

**Request Body:**
- `title` (string, required) - The title of the post.
- `content` (string, required) - The content of the post.
- `category` (string, optional) - The category of the post.
- `tags` (array of strings, optional) - Tags for the post.
- `file` (file, optional) - The featured image for the post.

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
{
    "id": 123,
    "title": "Post Title",
    "content": "Post content.",
    "date": "2024-08-10",
    "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/image.jpg",
    "category": "Category Name",
    "tags": ["Tag1", "Tag2"]
}
```



### 2. Get All Posts

**URL:** `/wp-json/custom/v1/posts`  
**Method:** `GET`  
**Description:** Retrieves all published blog posts.

**Headers:**
- **Authorization:** Bearer YOUR_JWT_TOKEN (optional)

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
[
    {
        "id": 123,
        "title": "Post Title",
        "content": "Post content.",
        "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/image.jpg",
        "date": "2024-08-10",
        "author": "Author Name",
        "category": "Category Name",
        "tags": ["Tag1", "Tag2"]
    }
]
```

### 3. Get Post by ID

**URL:** `/wp-json/custom/v1/post/{id}`  
**Method:** `GET`  
**Description:** Retrieves a single blog post by ID.

**Headers:**
- **Authorization:** Bearer YOUR_JWT_TOKEN (optional)

**URL Parameters:**
- `id` (integer, required) - The ID of the post.

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
{
    "id": 123,
    "title": "Post Title",
    "content": "Post content.",
    "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/image.jpg",
    "date": "2024-08-10",
    "author": "Author Name",
    "category": "Category Name",
    "tags": ["Tag1", "Tag2"]
}
```


### 4. Update Post by ID

**URL:** `/wp-json/custom/v1/update-post/{id}`  
**Method:** `POST`  
**Description:** Updates an existing blog post by ID.

**Headers:**
- `Content-Type: multipart/form-data`
- **Authorization:** Bearer YOUR_JWT_TOKEN

**URL Parameters:**
- `id` (integer, required) - The ID of the post to update.

**Request Body:**
- `title` (string, required) - The new title of the post.
- `content` (string, required) - The new content of the post.
- `category` (array of strings, optional) - The categories to assign to the post.
- `tags` (array of strings, optional) - The tags to assign to the post.
- `file` (file, optional) - The new featured image for the post.

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
{
    "message": "Post updated successfully",
    "id": 123,
    "title": "Updated Post Title",
    "content": "Updated post content.",
    "date": "2024-08-10",
    "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/new-image.jpg",
    "category": "Updated Category Name",
    "tags": ["NewTag1", "NewTag2"]
}
```


### 5. Delete Post by ID

```markdown
### 5. Delete Post by ID

**URL:** `/wp-json/custom/v1/post/{id}`  
**Method:** `DELETE`  
**Description:** Deletes a blog post by ID.

**Headers:**
- **Authorization:** Bearer YOUR_JWT_TOKEN

**URL Parameters:**
- `id` (integer, required) - The ID of the post to delete.

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
{
    "message": "Post deleted successfully",
    "id": 123
}
```