# Deployment Guide

This guide explains how to set up and use the automated deployment for the Alimurtado application.

## 1. Server Setup

Ensure your production server has:
- Docker installed
- Docker Compose installed
- SSH access

Create the directory for the application:
```bash
sudo mkdir -p /var/www/alimurtado
sudo chown -R ubuntu:ubuntu /var/www/alimurtado  # Replace 'ubuntu' with your SSH user
```

## 2. GitHub Secrets

Go to your GitHub Repository -> **Settings** -> **Secrets and variables** -> **Actions** -> **New repository secret**.

Add the following secrets:

| Secret Name | Description |
| :--- | :--- |
| `HOST` | Your server's IP address or domain. |
| `USERNAME` | The SSH username (e.g., `ubuntu`). |
| `KEY` | The content of your SSH private key. |
| `PORT` | The SSH port (usually `22`). |
| `DB_USER` | The database username. |
| `DB_PASS` | The database password. |
| `DB_NAME` | The database name. |

## 3. How to Deploy

The deployment is automated using GitHub Actions.

1.  Push any changes to the `main` branch.
2.  Go to the **Actions** tab in your repository to see the deployment progress.
3.  The workflow will:
    - SSH into your server.
    - Copy the latest code.
    - Update the `.env` file with your secrets.
    - Rebuild and restart the Docker containers.

## 4. Local Development

To run the application locally:

```bash
docker-compose up -d --build
```

Access the application at `http://localhost:8080`.
