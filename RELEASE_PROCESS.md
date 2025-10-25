# Release Process

## Pre-Release Requirements

Before creating a release, ensure that:

1. **All tests pass** - The GitHub Actions workflow automatically runs tests on every push
2. **No regressions detected** - Visual regression tests must pass
3. **Cross-platform compatibility** - Tests run on multiple PHP versions (8.1, 8.2, 8.3)

## Release Workflow

### Automatic Validation

When a release is created on GitHub:

1. **Release Validation Workflow** (`release-validation.yml`) automatically triggers
2. **Comprehensive Testing** runs on Ubuntu with PHP 8.3
3. **System Dependencies** are installed (Inkscape, ImageMagick, librsvg2-bin)
4. **All Regression Tests** are executed
5. **Validation Results** are uploaded as artifacts

### Manual Pre-Release Checklist

Before creating a release, manually verify:

- [ ] All tests pass locally: `cd test && php regression_test.php`
- [ ] No visual regressions in test images
- [ ] Version number updated in `qrcodesvg.php`
- [ ] CHANGELOG.md updated (if applicable)
- [ ] README.md reflects any new features

### Creating a Release

1. **Create Release on GitHub**:
   - Go to GitHub → Releases → Create a new release
   - Tag version (e.g., `v1.2.3`)
   - Add release notes
   - Mark as "Latest release"

2. **Automatic Validation**:
   - GitHub Actions will automatically validate the release
   - Tests must pass for the release to be considered valid
   - Validation results are available in the Actions tab

3. **Release Artifacts**:
   - Validation results are uploaded as artifacts
   - Download and verify if needed

## Test Coverage

The release validation includes:

- **90 test cases** covering all QR code variants
- **Visual regression testing** with PNG comparison
- **Multiple rendering modes** (Path vs Elements)
- **Color combinations** with transparency
- **Cross-platform SVG to PNG conversion**

## Failure Handling

If release validation fails:

1. **Check the Actions tab** for detailed error logs
2. **Download test artifacts** to analyze differences
3. **Fix issues** and create a new release
4. **Re-run validation** by creating a new release

## Success Criteria

A release is considered valid when:

- ✅ All 90 regression tests pass
- ✅ No visual differences detected
- ✅ SVG to PNG conversion works correctly
- ✅ All PHP versions (8.1, 8.2, 8.3) are supported
