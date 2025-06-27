# Contributing to Userbird-analytics

Thank you for your interest in contributing to Userbird! This document provides guidelines for contributing to this WordPress plugin and explains our workflow between GitHub and WordPress.org's SVN repository.

## Plugin Documentation Management

### README Files

We maintain two different README files for different purposes:

1. **readme.txt** - The WordPress.org plugin repository requires this specific format
2. **README.md** - GitHub uses this for repository documentation

⚠️ **IMPORTANT**: Only edit the `readme.txt` file! The `README.md` file is automatically generated.

We use GitHub Actions to automatically convert `readme.txt` to `README.md` whenever changes are pushed to the main branch. This ensures both files stay in sync without manual intervention.

## Deployment Process

### GitHub to WordPress.org SVN

We use GitHub Actions to automatically deploy the plugin to WordPress.org's SVN repository when a new tag is pushed. This is how the process works:

1. **Development happens on GitHub**:
   - Make your changes and commit them to your branch
   - Create a pull request to the main branch
   - Once approved and merged, your changes will be in the main branch

2. **Release Process**:
   - Update the version number in both `userbird.php` and `readme.txt`
   - Add a changelog entry in `readme.txt` under the "Changelog" section
   - Commit these changes to the main branch
   - Create and push a new tag matching your version number:
     ```bash
     git tag 1.2.0
     git push origin 1.2.0
     ```
   - The GitHub Action will automatically deploy the new version to WordPress.org SVN

3. **What the Action Does**:
   - Checks out the code at the tagged version
   - Deploys it to the WordPress.org SVN repository
   - Handles assets in the `.wordpress-org` directory (if present)
   - Creates a new version in the WordPress.org repository

### File Filtering for SVN

To ensure only relevant files get deployed to WordPress.org:

1. We use a `.distignore` file to specify which files and directories should NOT be included in the SVN deployment
2. The following are automatically excluded:
   - GitHub-specific files (`.github/`, workflows, etc.)
   - Development files (`.git/`, package.json, webpack configs, etc.)
   - Build artifacts not needed for production
   - This CONTRIBUTING.md file and README.md (as readme.txt is used by WordPress.org)

### Understanding Plugin Assets vs. WordPress.org Assets

There are two different types of "assets" in WordPress plugin development:

1. **Plugin Assets** (`assets/` directory):
   - These are files used by the plugin itself
   - CSS, JS, images used in the admin or frontend
   - These files WILL be included in the plugin deployment
   - Example: `assets/userbird-admin.css`, `assets/images/userbird-icon.svg`

2. **WordPress.org Repository Assets** (`.wordpress-org/` directory):
   - These are files used by the WordPress.org plugin repository
   - Icons, banners, and screenshots shown on your plugin's page
   - These will be moved to the SVN `assets/` directory (not bundled with the plugin)
   - Example: `.wordpress-org/banner-772x250.jpg`, `.wordpress-org/icon-256x256.png`

The GitHub Action handles these two asset types differently:
- Regular plugin assets in `assets/` are deployed with the plugin code
- WordPress.org repository assets in `.wordpress-org/` are moved to the SVN `assets/` directory

### Manual SVN Deployment (if needed)

If you need to manually deploy to WordPress.org SVN:

1. Check out the SVN repository:
   ```bash
   svn checkout https://plugins.svn.wordpress.org/userbird/
   ```

2. Copy the current plugin files to the `trunk` directory (excluding files in `.distignore`)

3. For new releases, copy the `trunk` to a new tag directory:
   ```bash
   svn cp trunk tags/1.2.0
   ```

4. Commit the changes:
   ```bash
   svn ci -m "Release 1.2.0"
   ```

## Code Guidelines

- Follow WordPress coding standards
- Ensure your code passes WordPress plugin checks
- Update version numbers in both `userbird.php` and `readme.txt`
- Update the changelog in `readme.txt` with each version change

## WordPress.org Repository Assets

WordPress.org repository assets should be placed in the `.wordpress-org` directory:

- `banner-772x250.jpg` - Plugin banner for WordPress.org
- `icon-256x256.png` - Plugin icon for WordPress.org
- `screenshot-1.png`, `screenshot-2.png`, etc. - Screenshots shown on the plugin page

These assets will be automatically deployed to the WordPress.org SVN repository's `assets` directory and will be displayed on your plugin's page on WordPress.org.

## Questions or Issues?

If you have any questions or issues with the contribution process, please open an issue on the GitHub repository.