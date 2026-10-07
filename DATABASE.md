# Database Schema

## Overview
The Sorted database uses SQLite and consists of the following main tables for managing family tasks and chores.

## Architecture

### Templates vs Tasks
- **Templates** are the blueprints/schedules that define recurring tasks (e.g., "Feed the dog daily")
- **Tasks** are individual instances of templates that are created for specific dates and can be completed/skipped
- Templates are never directly actioned - only Tasks are completed or skipped
- A scheduled command runs nightly to ensure each Template has a pending Task for its next occurrence

## Tables

### users
Standard Laravel users table for family members.
- `id` - Primary key
- `name` - Family member's name
- `email` - Email address
- `password` - Hashed password
- `timestamps`

### templates
Blueprints for recurring tasks. Defines the schedule and metadata.
- `id` - Primary key
- `parent_id` - Foreign key for subtemplates (nullable, max 1 level deep)
- `title` - Task name (required)
- `description` - Detailed instructions (optional)
- `points` - Points earned for completion (default: 0)
- `deadline` - Optional due date (for one-time templates)
- `recurrence_type` - Enum: 'none', 'daily', 'weekly', 'biweekly', 'monthly', 'custom' (default: 'none')
- `recurrence_pattern` - JSON field for custom recurrence patterns
- `next_due_date` - When the template's next task is due
- `timestamps`

### tasks
Individual task instances created from templates. These are what users actually complete.
- `id` - Primary key
- `template_id` - Foreign key to templates
- `parent_id` - Foreign key for subtasks (nullable, references tasks)
- `date` - The date this task instance is for
- `status` - Enum: 'todo', 'done', 'skipped' (default: 'todo')
- `user_id` - Foreign key to users (who completed it, nullable)
- `completed_at` - When it was completed
- `notes` - Optional completion notes
- `timestamps`

### labels
Categorization tags for templates (e.g., Cleaning, Kitchen, Pets).
- `id` - Primary key
- `name` - Label name
- `color` - Hex color code for UI display (optional)
- `timestamps`

### label_template (pivot)
Many-to-many relationship between labels and templates.
- `id` - Primary key
- `label_id` - Foreign key to labels
- `template_id` - Foreign key to templates
- `timestamps`

### template_user (pivot)
Many-to-many relationship for template assignments.
- `id` - Primary key
- `template_id` - Foreign key to templates
- `user_id` - Foreign key to users (assigned to)
- `timestamps`

## Relationships

### Template Model
- `users()` - BelongsToMany User (who can do this task)
- `labels()` - BelongsToMany Label (categorization)
- `tasks()` - HasMany Task (individual instances)
- `parent()` - BelongsTo Template (for subtemplates)
- `subtemplates()` - HasMany Template (child templates, 1 level only)

### Task Model
- `template()` - BelongsTo Template
- `user()` - BelongsTo User (who completed it)
- `parent()` - BelongsTo Task (for subtasks)
- `subtasks()` - HasMany Task (child tasks)

### User Model
- `templates()` - BelongsToMany Template (assigned templates)

### Label Model
- `templates()` - BelongsToMany Template

## Task Generation

Tasks are automatically generated in two ways:

1. **Scheduled Command**: `php artisan tasks:generate` runs nightly at midnight to ensure each template has a pending task for its next occurrence.

2. **Model Events**: When a Template is created or updated (if recurrence fields change), it automatically creates/updates its pending task.

When a parent template has subtemplates, creating a task for the parent also creates subtasks for all subtemplates on the same date.

## Recurrence System

### recurrence_type Options
| Type | Description |
|------|-------------|
| `none` | One-time task (may or may not have a deadline) |
| `daily` | Every day |
| `weekly` | Weekly - flexible or on specific days |
| `biweekly` | Every 2 weeks |
| `monthly` | Monthly - flexible or on specific dates |

### recurrence_pattern (JSON)

The `recurrence_pattern` field stores the specific configuration:

#### Flexible Periods (do it anytime during the period)
```json
{ "flexible": true }
```

#### Fixed Days of Week (for weekly/biweekly)
```json
{ "days_of_week": [3, 6] }
```
- 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday
- Example: `[3, 6]` = every Wednesday and Saturday

#### Fixed Days of Month (for monthly)
```json
{ "days_of_month": [1, 15] }
```
- Example: `[1, 15]` = 1st and 15th of every month

### Examples

| Use Case | recurrence_type | recurrence_pattern |
|----------|-----------------|-------------------|
| Do weekly, any day | `weekly` | `{"flexible": true}` |
| Every Wednesday | `weekly` | `{"days_of_week": [3]}` |
| Every Wed & Sat | `weekly` | `{"days_of_week": [3, 6]}` |
| Every 2 weeks on Wed | `biweekly` | `{"days_of_week": [3]}` |
| Do monthly, any day | `monthly` | `{"flexible": true}` |
| 15th of each month | `monthly` | `{"days_of_month": [15]}` |
| 1st & 15th of month | `monthly` | `{"days_of_month": [1, 15]}` |
| Every day | `daily` | `null` |
| One-time with deadline | `none` | `null` (use `deadline` field) |
| No schedule | `none` | `null` |

## URL Structure

- `/` - Home view (today's tasks)
- `/templates` - List all templates
- `/templates/create` - Create new template
- `/templates/{id}` - View template details and analytics
- `/tasks/{id}/complete` - Complete a task (POST)
- `/tasks/{id}/skip` - Skip a task (POST)
- `/tasks/{id}/reset` - Reset a task back to todo (POST)
