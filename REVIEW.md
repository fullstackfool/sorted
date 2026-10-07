# Sorted Product Review

## Completed

- [x] **Dead Home.vue file** — Removed unused `Home.vue` (HomeController already renders `Today/Index`)
- [x] **Dead UI buttons** — Implemented full user CRUD (create, edit, delete) + DiceBear avatar picker with history navigation

## To Review

### Critical

3. **"All Done for Today" logic ignores filters** — The "All done!" message shows when filtered task groups are empty, not when all tasks are actually done. User filters to their own tasks, sees "All done!", clears filter — other tasks still pending.

### Medium — Product Logic

4. **Subtask completion model is ambiguous** — No enforced relationship between parent task completion and subtask status. Can you mark a parent "done" with incomplete subtasks? Does completing all subtasks auto-complete the parent? UI gives no guidance.
5. **No task notes input at completion time** — `tasks.notes` field exists in DB and shows in Users/Show, but Today view has no way to add notes when completing.
6. **Filter state not persisted** — Navigating away from Today and back resets all filters. Should use URL query params.

### Medium — UX / ADHD

7. **Template creation too complex** — No smart defaults, no schedule preview, no "Select All"/"Clear All" for day grids.
8. **No "all tasks done" super-celebration** — Celebration fires per-task but no special payoff when the whole day is cleared.
9. **Two-tap completion flow** — Must tap Complete then select user. Consider remembering last user or showing user buttons directly.
10. **No reminders or notifications** — App is passive; no browser notifications or sounds at task due times.
11. **Scoreboard lacks explanation** — What does "weekly" mean? How are points calculated? No tooltip or help text.

### Medium — Accessibility

12. **Touch targets too small** — Skip/Reset buttons use small icons (~20px). Tablet minimum is 48x48px.
13. **Color-only status indicators** — Task status differentiated only by text color, no icons for colorblind users.

### Low

14. **Avatar colors inconsistent** — Each component hardcodes avatar gradients. Should use shared composable. *(Partially addressed by DiceBear avatars — fallback initials still vary)*
15. **Recurrence display shows raw type** — Templates/Index shows "weekly" instead of "Every Monday and Wednesday".
16. **Reset button uses blue (primary) color** — Should be gray or yellow for a secondary action.
17. **Dark theme hard to see in bright kitchen** — No light mode or brightness option.
18. **Date formatting inconsistent** — Different format strings across views.

### Deployment

19. **deploy.sh PHP-FPM restart uses wildcard** — `php*-fpm` will fail with multiple PHP versions.
20. **Session driver overhead** — Database sessions for a no-auth app waste Pi resources.
21. **npm install in deploy instead of npm ci --production** — Installs dev deps unnecessarily.
22. **Zero test coverage** — Only boilerplate example tests exist.

### Feature Gaps

23. **Bulk actions** — Can't mark multiple tasks done at once.
24. **Task time estimates** — No quick/medium/long indicator (important for ADHD).
25. **"Needs assignment" badge** — Unassigned tasks have no visual distinction.
26. **User management working** — ~~Not implemented~~ Done!
