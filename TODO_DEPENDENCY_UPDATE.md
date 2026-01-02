# TODO: Update dependencies for React 18 and WordPress modern versions

## Objective
Eliminate incompatible dependencies and adjust code to work with modern versions of React and WordPress.

## Tasks

### 1. Remove react-autosize-textarea override
- [ ] Remove `react-autosize-textarea` override from `packages/client/package.json`
- [ ] Remove related override from `@wordpress/block-editor`

### 2. Update ajv/ajv-keywords versions
- [ ] Update ajv to consistent version ^8.17.1
- [ ] Update ajv-keywords to ^5.1.0
- [ ] Remove node_modules and package-lock.json
- [ ] Run npm install --legacy-peer-deps

### 3. Improve TypeScript store types
- [ ] Update `packages/client/src/store/index.ts` to use proper StoreDescriptor types
- [ ] Remove `as any` casts where possible
- [ ] Update `packages/client/src/store/selectors.ts` with proper typing
- [ ] Update `packages/client/src/api.ts` with proper typing

### 4. Add react-textarea-autosize (optional)
- [ ] Install react-textarea-autosize package
- [ ] Update any components that need autosize textarea

### 5. Verify and test
- [ ] Run npm install
- [ ] Run npm run build
- [ ] Run lint checks

## Notes
- React 18.3.1 and ReactDOM 18.3.1 are already correct
- @wordpress packages are already on modern versions
- apiFetch already uses the modern API with options object

