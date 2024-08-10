## Story API Endpoints

### 1. Create Story

**URL:** `/wp-json/custom/v1/story`  
**Method:** `POST`  
**Description:** Creates a new story.

**Headers:**
- `Content-Type: multipart/form-data`
- **Authorization:** Bearer YOUR_JWT_TOKEN

**Request Body:**
- `title` (string, required) - The title of the story.
- `content` (string, required) - The content of the story.
- `file` (file, optional) - The featured image for the story.

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
{
    "id": 123,
    "title": "Story Title",
    "content": "Story content.",
    "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/image.jpg",
    "date": "2024-08-10"
}
```

## Get All Stories

**URL:** `/wp-json/custom/v1/stories`  
**Method:** `GET`  
**Description:** Retrieves all published stories.

**Headers:**
- **Authorization:** Bearer `YOUR_JWT_TOKEN` (optional)

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
[
    {
        "id": 123,
        "title": "Story Title",
        "content": "Story content.",
        "excerpt": "Story excerpt.",
        "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/image.jpg",
        "date": "2024-08-10",
        "author": "Author Name"
    },
    {
        "id": 124,
        "title": "Another Story Title",
        "content": "Another story content.",
        "excerpt": "Another story excerpt.",
        "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/another-image.jpg",
        "date": "2024-08-09",
        "author": "Another Author Name"
    }
]
```

## Get Story by ID

**URL:** `/wp-json/custom/v1/story/{id}`  
**Method:** `GET`  
**Description:** Retrieves a single story by ID.

**Headers:**
- **Authorization:** Bearer `YOUR_JWT_TOKEN` (optional)

**URL Parameters:**
- `id` (integer, required) - The ID of the story to retrieve.

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
{
    "id": 123,
    "title": "Story Title",
    "content": "Story content.",
    "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/image.jpg",
    "date": "2024-08-10",
    "author": "Author Name"
}
```

## Update Story by ID

**URL:** `/wp-json/custom/v1/update-story/{id}`  
**Method:** `POST`  
**Description:** Updates an existing story by ID.

**Headers:**
- `Content-Type: multipart/form-data`
- **Authorization:** Bearer `YOUR_JWT_TOKEN`

**URL Parameters:**
- `id` (integer, required) - The ID of the story to update.

**Request Body:**
- `title` (string, required) - The new title of the story.
- `content` (string, required) - The new content of the story.
- `category` (array of strings, optional) - The categories to assign to the story.
- `tags` (array of strings, optional) - The tags to assign to the story.
- `file` (file, optional) - The new featured image for the story.

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
{
    "message": "Story updated successfully",
    "id": 123,
    "title": "Updated Story Title",
    "content": "Updated story content.",
    "date": "2024-08-10",
    "feature_image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/new-image.jpg",
    "file_uploaded": "path/to/uploaded/file.jpg" // If a new file was uploaded
}
```
## Delete Story by ID

**URL:** `/wp-json/custom/v1/story/{id}`  
**Method:** `DELETE`  
**Description:** Deletes a story by ID.

**Headers:**
- **Authorization:** Bearer `YOUR_JWT_TOKEN`

**URL Parameters:**
- `id` (integer, required) - The ID of the story to delete.

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
{
    "message": "Story deleted successfully",
    "id": 123
}
```

