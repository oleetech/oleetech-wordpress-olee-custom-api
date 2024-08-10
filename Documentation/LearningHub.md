# LearningHub API Documentation

## Overview

This API provides CRUD operations for managing LearningHub posts in WordPress. It includes endpoints to create, retrieve, update, and delete LearningHub posts.

## Endpoints

### 1. Create LearningHub

- **URL:** `/wp-json/custom/v1/learninghub`
- **Method:** `POST`
- **Auth Required:** Yes (User must be logged in)
- **Content-Type:** `multipart/form-data` (for file uploads)

#### Request Parameters

- `title` (string) - Title of the LearningHub post.
- `content` (string) - Content of the LearningHub post.
- `youtube_link` (string) - YouTube link to be embedded in the post.
- `category` (string) - Category for the LearningHub post.
- `tags` (array) - Tags associated with the LearningHub post.
- `file` (file) - Optional. Feature image to be uploaded with the post.

#### Response

- **Success:**

  ```json
  {
    "id": 123,
    "title": "Sample Title",
    "content": "Sample Content",
    "feature_image_url": "http://example.com/path/to/image.jpg",
    "youtube_link": "https://youtube.com/samplevideo",
    "category": "Sample Category",
    "tags": ["tag1", "tag2"],
    "author": "Author Name",
    "date": "2024-08-10"
  }

```


### 2. Get LearningHub by ID

- **URL:** `/wp-json/custom/v1/learninghub/{id}`
- **Method:** `GET`
- **Auth Required:** No (Publicly accessible)

#### URL Parameters

- `id` (integer) - The ID of the LearningHub post to retrieve.

#### Response

- **Success:**

  ```json
  {
    "id": 123,
    "title": "Sample Title",
    "content": "Sample Content",
    "youtube_link": "https://youtube.com/samplevideo",
    "category": "Sample Category",
    "tags": ["tag1", "tag2"],
    "author": "Author Name",
    "date": "2024-08-10"
  }
  
```