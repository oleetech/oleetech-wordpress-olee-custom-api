## Image Upload API Endpoint

### URL
`/wp-json/custom/v1/upload-image`

### Method
`POST`

### Description
Uploads an image file and returns the ID and URL of the uploaded image.

### Headers
- `Content-Type: multipart/form-data`
- **Authorization:** Bearer YOUR_JWT_TOKEN

### Request Body
- **File:** `file` (required)
  - The file to be uploaded should be included in the `file` parameter of the form data.

### Response

#### Success Response
**Status Code:** 200 OK  
**Body:**
```json
{
    "image_id": 123,
    "image_url": "https://yourwebsite.com/wp-content/uploads/2024/08/filename.jpg"
}
```