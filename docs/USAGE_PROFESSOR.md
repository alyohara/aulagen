# Teacher guide

## AulaGen at a glance

AulaGen converts educator-provided materials into a structured virtual classroom. It can suggest a curriculum, lesson content, activities, and a glossary, but teachers control what students see. AI-generated material should always be reviewed before publication.

## Roles

| Role | Main capabilities |
| --- | --- |
| Administrator | Manage users, global AI settings, and generation records |
| Teacher | Create and manage courses, upload materials, review generated content, and publish courses |
| Student | Visit published classrooms, read lessons, complete activities, search, and ask course-grounded questions |

## Create a course

1. Sign in with a Teacher or Administrator account.
2. From the dashboard, choose **Create course**.
3. Enter the course name and, when useful, its description, objectives, institution, and program.
4. Save the course. It remains private until you publish it.

## Add teaching materials

Open a course and use its **Materials** area to add files or external resources. The application provides extractors for PDF, DOCX, PPTX, XLSX, and plain-text documents. Links and video references can also be added as external resources.

When a file is uploaded, AulaGen:

1. Extracts its text and metadata.
2. Splits the source into smaller searchable chunks.
3. Creates embeddings when the selected provider supports them.
4. Stores the processed material as the knowledge base for the course.

Check the material's processing status before generating a curriculum. A failed status contains an error message; correct the source file or configuration and reprocess it.

## Generate a proposed curriculum

1. Open the course **Content** area.
2. Request an AI structure proposal.
3. Review the proposed modules and lessons.
4. Apply the proposal only when it represents the course correctly.
5. Reorder, edit, add, or remove modules and lessons as needed.

The proposal is based on the processed source material. It is not automatically visible to students.

## Generate learning content

From the course content and activity areas, teachers can request AI-generated lesson content, questions, activities, and glossary entries. Review the text, source relationships, and learning goals before changing its status or publishing the course.

The AI configuration controls the active provider and rate limits. If a configured provider fails, AulaGen can use its local fallback according to the system settings.

## Review and publish

The teacher workflow is intentionally review-first:

1. Edit the generated structure or content.
2. Approve the material you want to make available.
3. Use the course preview to check the student experience.
4. Publish the course when the classroom is ready.

Unpublish a course to remove its public student view while you make further changes.

## Student classroom

Students use the published classroom URL. The experience can include:

- Module and lesson navigation
- Lesson progression tracking
- Self-check activities and answer feedback
- Bibliography and complementary materials
- Semantic course search
- A glossary
- An AI assistant that answers from course material and shows sources when enabled

## Course assistant and privacy

The assistant uses retrieval-augmented generation (RAG). It retrieves relevant course chunks, sends them as context to the selected AI provider, and asks the model to answer only from that context. The course settings can disable the assistant, hide source labels, or allow explicitly marked external knowledge.

Before enabling a cloud provider, ensure that sending teaching material to that provider is permitted by your institution and complies with applicable privacy policies. A local Ollama installation can be used when a self-hosted model is preferable.

## Managing users and AI settings

Administrators can open the administration area to:

- Change a user's role or active status
- Inspect AI generation history
- Select and test the active AI provider
- Update provider URL, model, and API-key settings

API keys are masked in the administration interface. Store them in environment configuration or secure deployment secrets and never commit them to the repository.

## Recommended teaching workflow

1. Create the course and describe its objectives.
2. Upload the source material and wait until it is processed.
3. Generate a structure proposal and correct it against your syllabus.
4. Generate lessons and activities one area at a time.
5. Review every AI output for correctness, pedagogy, accessibility, and citations.
6. Preview the student classroom.
7. Publish only the material ready for students.

## Troubleshooting

| Issue | What to check |
| --- | --- |
| Material remains pending or fails | File type, file size, Laravel queue worker, and the processing error message |
| AI generation fails | Provider selection, connection test, API key, model name, rate limit, and queue worker |
| Assistant cannot answer | Whether the course has processed material, whether the assistant is enabled, and whether matching source chunks exist |
| Students cannot see content | Course publication state and the approval or visibility status of the relevant material |
