# Continuous Deployment

This document explains how Continuous Deployment (CD) is set up in this project and how developers can contribute and deploy changes safely.

## Overview

We use **GitHub Actions** as our CI/CD pipeline to automate the deployment process:

1. **Build**: Docker images are built from the project source.
2. **Publish**: Docker images are pushed to [GitHub Container Registry (ghcr.io)](https://ghcr.io).
3. **Deploy**: The latest `docker-compose.yml` file is transferred to the server via SSH.
4. **Restart**: On the server, the updated containers are pulled from ghcr.io and the service is restarted.

This ensures that every change merged into the main branch is automatically built, shipped, and deployed without manual intervention.

---

## Branching Strategy

- The **`main` branch** is the **production branch** and is **protected**.
- **Direct pushes to `main` are not allowed**.
- All changes must go through a **pull request (PR)** process.

---

## How to Contribute Changes

To submit new changes:

1. **Create a feature branch**:
```bash
git checkout -b feature/my-new-feature
````

2. **Commit your changes**:

```bash
git add .
git commit -m "Add: new feature"
```

3. **Push the branch**:

```bash
git push origin feature/my-new-feature
```

4. **Open a Pull Request**:

* Go to the GitHub repository page.
* Click **"Compare & pull request"**.
* Describe your changes clearly.
* Wait for code review and approvals.

5. **Merge into `main`**:

* Once approved, the PR can be merged.
* Merging into `main` will trigger the automated deployment process.

---

## Troubleshooting

If something goes wrong after deployment:

* Check the GitHub Actions logs for the latest run.
* SSH into the server to manually inspect container logs:

```bash
docker compose logs -f
```
* If needed, you can restart the service manually:

```bash
docker compose pull
docker compose up -d
```

---

For any issues or questions, please contact a maintainer or open an issue in the repository.
