### Questions API

This API provides CRUD operations for managing questions in WordPress. It includes endpoints to create, retrieve, update, and delete questions.

#### Endpoints

##### 1. Create Question

- **URL:** `/wp-json/custom/v1/questions`
- **Method:** `POST`
- **Auth Required:** Yes (User must be logged in)
- **Content-Type:** `application/json`

###### Request Parameters

- `title` (string, required) - Title of the question.
- `mcq` (string, optional) - Comma-separated list of MCQ options.

###### Example Request

```http
POST /wp-json/custom/v1/questions HTTP/1.1
Host: example.com
Content-Type: application/json

{
  "title": "What is the capital of France?",
  "mcq": "Paris,London,Berlin,Madrid"
}
```


### 2. Get All Questions

- **URL:** `/wp-json/custom/v1/questions`
- **Method:** `GET`
- **Auth Required:** No

#### Response

- **Success:**

  ```json
  [
    {
      "id": 123,
      "title": "What is the capital of France?",
      "question_type": "mcq",
      "mcq_options": ["Paris", "London", "Berlin", "Madrid"]
    },
    {
      "id": 124,
      "title": "What is the largest planet in our solar system?",
      "question_type": "mcq",
      "mcq_options": ["Jupiter", "Saturn", "Earth", "Mars"]
    }
  ]

```


### 3. Get Question by ID

- **URL:** `/wp-json/custom/v1/questions/{id}`
- **Method:** `GET`
- **Auth Required:** No

#### URL Parameters

- **`id`** (integer) - The unique identifier of the question you want to retrieve.

#### Response

- **Success:**

  ```json
  {
    "id": 123,
    "title": "What is the capital of France?",
    "question_type": "mcq",
    "mcq_options": ["Paris", "London", "Berlin", "Madrid"]
  }
```

### 4. Update Question

- **URL:** `/wp-json/custom/v1/questions/{id}`
- **Method:** `PUT`
- **Auth Required:** Yes (User must have permission to edit posts)

#### URL Parameters

- **`id`** (integer) - The unique identifier of the question you want to update.

#### Request Body

- **`title`** (string) - The updated title of the question.
- **`type`** (string) - The updated type of the question (e.g., "text" or "mcq").
- **`mcq`** (array) - Optional. Comma-separated string of multiple-choice options, if the question is of type "mcq".

#### Example Request

```json
{
  "title": "What is the capital of Germany?",
  "type": "mcq",
  "mcq": "Berlin,Frankfurt,Munich,Hamburg"
}
```

### 5. Delete Question

- **URL:** `/wp-json/custom/v1/questions/{id}`
- **Method:** `DELETE`
- **Auth Required:** Yes (User must have permission to delete posts)

#### URL Parameters

- **`id`** (integer) - The unique identifier of the question you want to delete.

#### Example Request

```http
DELETE /wp-json/custom/v1/questions/123
```