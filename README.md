# Sorted

A family chore and task management system designed to run on a Raspberry Pi for local network access via tablet (iPad/Android) in the kitchen.

## Project Context

Sorted is designed specifically for a **household of people with ADHD** to help stay on top of chores through gamification and visual feedback.

### Deployment Model
- Runs on a **shared wall-mounted tablet** in a communal area (e.g., kitchen)
- Always-on, accessible to all household members
- **No authentication** - no logged-in user concept
- All UI views are **shared views** that everyone sees

### User Interaction
When completing tasks, users select themselves from a modal to identify who did the work. This keeps the interface simple and friction-free while still tracking individual contributions.

### ADHD-Friendly Design Principles
- **Visual feedback** - Immediate, obvious responses to actions
- **Gamification** - Points, streaks, leaderboards for motivation
- **Instant gratification** - Celebrations and rewards for task completion
- **Visible progress** - Clear indicators of achievements
- **Social motivation** - Friendly competition among household members
- **Low friction** - Quick, easy interactions without barriers

## Setup

This application uses:
- Laravel (PHP framework)
- Inertia.js (for seamless single-page app experience)
- Vue 3 (frontend framework)
- Tailwind CSS v4 (styling)
- Vite (build tool)
- SQLite (database - perfect for Raspberry Pi deployment)

## Getting Started

1. Install dependencies:
```bash
composer install
npm install
```

2. Set up your environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Run migrations and seed sample data:
```bash
php artisan migrate
php artisan db:seed --class=TaskSystemSeeder
```

4. Start the development servers:

In one terminal:
```bash
php artisan serve
```

In another terminal:
```bash
npm run dev
```

5. Visit `http://localhost:8000` in your browser

## Features

- **Multiple users** - Manage tasks for all family members
- **Task assignments** - Assign tasks to multiple people or leave unassigned
- **Points system** - Award points for completed tasks (gamification)
- **Labels** - Categorize tasks with multiple labels (Cleaning, Kitchen, Pets, etc.)
- **Recurring tasks** - Daily, weekly, monthly, or custom recurrence patterns
- **Subtasks** - Break down complex tasks (1 level deep)
- **Deadlines** - Optional due dates for tasks
- **Task status** - todo, done, or skipped
- **Completion history** - Track who completed what and when

## Project Structure

- `routes/web.php` - Application routes
- `resources/js/Pages/` - Vue components (Inertia pages)
- `resources/views/app.blade.php` - Main Inertia layout
- `app/Models/` - Eloquent models (Task, Label, User, TaskCompletion)
- `database/migrations/` - Database schema
- `DATABASE.md` - Complete database documentation

## Database

See [DATABASE.md](DATABASE.md) for complete schema documentation.

The system uses SQLite with the following main tables:
- `users` - Family members
- `tasks` - Tasks with recurrence, points, status, and deadlines
- `labels` - Categorization tags
- `task_completions` - History of completed tasks
- Pivot tables for many-to-many relationships
