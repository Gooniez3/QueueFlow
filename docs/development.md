# Development workflow

## Guiding approach

QueueFlow is both a product and a learning project. Each phase should begin
with a small, manually understood exercise, followed by the production feature.
Code should be explainable by its author before it is merged.

## Branches

- `main` is the stable integration branch and should remain buildable.
- Create short-lived branches from `main` using:
  - `feature/<short-name>` for product work
  - `fix/<short-name>` for defects
  - `docs/<short-name>` for documentation-only work
  - `chore/<short-name>` for tooling or maintenance
- Merge through a reviewed pull request once checks pass.
- Delete merged topic branches to keep the repository tidy.

A long-lived `develop` branch is unnecessary at this project size. Release
milestones are represented by annotated tags such as `v1.0.0`.

## Commits

- Keep commits focused and leave the repository in a coherent state.
- Use an imperative subject, for example: `Add queue join validation`.
- Explain the reason for non-obvious changes in the commit body.
- Do not commit credentials, generated dependencies, build output, or local
  database data.

## Pull request checklist

- The behavior and motivation are clearly described.
- Relevant Java and/or Laravel tests pass locally.
- API contract or architecture changes are documented.
- Validation, authorization, tenant boundaries, and failure states were checked.
- Database changes include a migration and safe rollback/forward strategy.
- User-facing changes were checked for keyboard access and useful errors.

## Phase discipline

1. Confirm the learning objective and acceptance scenario.
2. Make the smallest coherent implementation.
3. Run the narrow tests, then the relevant full suite.
4. Review the diff for secrets, generated files, and accidental scope changes.
5. Update documentation when a decision or contract changes.

## Local commands

Framework commands will be added after each application is scaffolded. Until
then, use the environment checks in `environment.md`.
