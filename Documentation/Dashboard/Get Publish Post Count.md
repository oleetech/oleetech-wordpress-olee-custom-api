# Custom API Endpoints Documentation

## 1. Get Published Posts Count

**Endpoint:** `/wp-json/custom/v1/get_published_posts_count`

**Method:** `GET`

**Description:** Retrieves the count of published posts for different post types and appointment statuses.

### Permissions
- Requires the user to have the `manage_options` capability.

### Response

```json
{
    "blog": 10,
    "story": 5,
    "learninghub": 7,
    "appointment": {
        "requested": 2,
        "approved": 3,
        "unapproved": 1
    }
}
```


## Get User Status Count

**Endpoint:** `/wp-json/custom/v1/get_user_status_count`

**Method:** `GET`

**Description:** Retrieves the count of appointments with different statuses, the count of wishlist learninghub IDs, and the date-wise sum of answers for a specific question for the current logged-in user.

### Permissions
- **Requires Authentication:** The user must be logged in to access this endpoint.

### Request

**Headers:**
- `Authorization: Bearer YOUR_ACCESS_TOKEN`

**Example Request:**

```bash
curl -X GET https://example.com/wp-json/custom/v1/get_user_status_count \
-H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```