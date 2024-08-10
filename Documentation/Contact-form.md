### Contact Form Submission

- **URL:** `/wp-json/custom/v1/contact-form`
- **Method:** `POST`
- **Auth Required:** No
- **Content-Type:** `application/json`

#### Request Parameters

- `name` (string, required) - Name of the person submitting the form.
- `email` (string, required) - Email address of the person submitting the form.
- `subject` (string, required) - Subject of the message.
- `message` (string, required) - Message content.

#### Example Request

```http
POST /wp-json/custom/v1/contact-form HTTP/1.1
Host: example.com
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "subject": "Inquiry about your services",
  "message": "I would like to know more about your services."
}
```