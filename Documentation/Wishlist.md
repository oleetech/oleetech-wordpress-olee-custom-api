## API Documentation

### 1. Add to Wishlist

**Endpoint:** `POST /wp-json/custom/v1/add-to-wishlist`

**Description:**  
Adds a LearningHub to the authenticated user's wishlist. If a wishlist does not exist for the user, a new one is created.

**Request Parameters:**
- `learninghub_id` (required): The ID of the LearningHub to add to the wishlist.

**Request Headers:**
- `Authorization`: Bearer token (JWT or Basic Authentication)

**Example Request:**
```bash
POST /wp-json/custom/v1/add-to-wishlist
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
    "learninghub_id": 123
}
```

### Responses:

Success (200 OK):

Response Body:


```json
{
    "wishlist_id": 1,
    "id": 123,
    "title": "Learning Hub Title",
    "content": "Content of the Learning Hub.",
    "youtube_link": "https://youtube.com/example",
    "category": "Category Name",
    "tags": ["Tag1", "Tag2"],
    "author": "Author Name",
    "date": "2024-01-01"
}

```


## Remove from Wishlist
Endpoint: POST /wp-json/custom/v1/remove-from-wishlist

***Description:***
Removes a LearningHub from the authenticated user's wishlist.

***Request Parameters:***

learninghub_id (required): The ID of the LearningHub to remove from the wishlist.
Request Headers:

Authorization: Bearer token (JWT or Basic Authentication)
***Example Request:***


POST /wp-json/custom/v1/remove-from-wishlist
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN
```json
{
    "learninghub_id": 123
}

```

***Responses:***

Success (200 OK):

Response Body:
```json
{
    "wishlist_id": 1,
    "id": 123,
    "title": "Learning Hub Title",
    "content": "Content of the Learning Hub.",
    "youtube_link": "https://youtube.com/example",
    "category": "Category Name",
    "tags": ["Tag1", "Tag2"],
    "author": "Author Name",
    "date": "2024-01-01"
}
```

## Get Learning Hubs
Endpoint: GET /wp-json/custom/v1/get-learning-hubs

***Description:***
Retrieves all LearningHubs in the authenticated user's wishlist.

***Request Headers:***

Authorization: Bearer token (JWT or Basic Authentication)
***Example Request:***

GET /wp-json/custom/v1/get-learning-hubs
Authorization: Bearer YOUR_TOKEN


***Responses:***

Success (200 OK):

Response Body:
```json
[
    {
        "id": 123,
        "title": "Learning Hub Title",
        "content": "Content of the Learning Hub.",
        "youtube_link": "https://youtube.com/example",
        "category": "Category Name",
        "tags": ["Tag1", "Tag2"],
        "author": "Author Name",
        "date": "2024-01-01"
    }
]

```

