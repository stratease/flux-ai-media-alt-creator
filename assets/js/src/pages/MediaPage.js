import React, { useState, useEffect, useMemo, useCallback } from 'react';
import {
  Box,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  Checkbox,
  Button,
  Pagination,
  Chip,
  Link,
  Skeleton,
  Alert,
  Stack,
  CircularProgress,
  FormGroup,
  FormControlLabel,
  TextField,
  Grid,
  InputAdornment,
  MenuItem,
  Select,
  FormControl,
  InputLabel,
  Tooltip,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  IconButton,
} from '@mui/material';
import { AutoAwesome, CheckCircle, ImageOutlined, Restore } from '@mui/icons-material';
import { __ } from '@wordpress/i18n';
import { useSearchParams } from 'react-router-dom';
import { useMedia, useMediaTypeGroups } from '../hooks/useMedia';
import { useGenerateAltText, useApplyAltText, useBatchGenerateAltText } from '../hooks/useAltText';
import { useSetAltCategory } from '../hooks/useCompliance';

/**
 * Media page component with paginated list and bulk actions
 * 
 * The filters state can be extended by other plugins to add custom search parameters.
 * Filters are passed to the backend and processed through WordPress hooks:
 * - flux_ai_alt_creator_search_mime_types
 * - flux_ai_alt_creator_additional_query_args
 * - flux_ai_alt_creator_scan_query_args
 * 
 * See HOOKS.md for documentation on extending search functionality.
 */
const ALT_CATEGORY_OPTIONS = [
  { value: 'all', label: __('All', 'flux-ai-media-alt-creator') },
  { value: 'missing', label: __('Missing', 'flux-ai-media-alt-creator') },
  { value: 'placeholder', label: __('Placeholder', 'flux-ai-media-alt-creator') },
  { value: 'duplicate', label: __('Duplicate', 'flux-ai-media-alt-creator') },
  { value: 'descriptive', label: __('Descriptive', 'flux-ai-media-alt-creator') },
  { value: 'contextual', label: __('Contextual', 'flux-ai-media-alt-creator') },
  { value: 'decorative', label: __('Decorative', 'flux-ai-media-alt-creator') },
  { value: 'woocommerce', label: __('WooCommerce Images', 'flux-ai-media-alt-creator') },
];

const MediaPage = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const urlCategory = searchParams.get('alt_category') || 'all';
  const [page, setPage] = useState(1);
  const [selectedMedia, setSelectedMedia] = useState(new Set());
  const [search, setSearch] = useState('');
  const [altCategory, setAltCategory] = useState(urlCategory);
  const [filters, setFilters] = useState({});
  const [selectedMediaTypes, setSelectedMediaTypes] = useState(['images']);
  const [editedAltTexts, setEditedAltTexts] = useState({});
  const [markDecorativeConfirmOpen, setMarkDecorativeConfirmOpen] = useState(false);
  const perPage = 20;
  const isWooCommerceActive = typeof window !== 'undefined' && window.fluxAIMediaAltCreatorAdmin?.isWooCommerceActive === true;

  // Sync URL alt_category into state on mount / when URL changes
  useEffect(() => {
    const q = searchParams.get('alt_category') || 'all';
    setAltCategory(q);
  }, [searchParams]);

  // Fetch available media type groups
  const { data: mediaTypeGroups, isLoading: loadingGroups } = useMediaTypeGroups();

  // Auto-select and disable if only one media type
  useEffect(() => {
    if (!loadingGroups && mediaTypeGroups && mediaTypeGroups.length === 1) {
      setSelectedMediaTypes([mediaTypeGroups[0].id]);
    }
  }, [loadingGroups, mediaTypeGroups]);

  // Update filters when media types or alt category change
  useEffect(() => {
    const next = {
      media_types: selectedMediaTypes.length > 0 ? selectedMediaTypes : ['images'],
    };
    if (altCategory && altCategory !== 'all') {
      next.alt_category = altCategory;
      if (altCategory === 'woocommerce') {
        next.woocommerce_only = true;
      }
    }
    setFilters(next);
  }, [selectedMediaTypes, altCategory]);

  // Clear selections when page changes
  useEffect(() => {
    setSelectedMedia(new Set());
  }, [page]);

  const { data, isLoading, error } = useMedia(page, perPage, search, filters);
  const generateMutation = useGenerateAltText();
  const applyMutation = useApplyAltText();
  const batchGenerateMutation = useBatchGenerateAltText();
  const setAltCategoryMutation = useSetAltCategory();

  // Memoize current page media IDs and create a Set for O(1) lookups
  const currentPageMediaIds = useMemo(() => {
    return new Set(data?.data?.map(item => item.id) || []);
  }, [data?.data]);

  // Memoize selected count on current page
  const selectedOnCurrentPageCount = useMemo(() => {
    let count = 0;
    currentPageMediaIds.forEach(id => {
      if (selectedMedia.has(id)) {
        count++;
      }
    });
    return count;
  }, [selectedMedia, currentPageMediaIds]);

  // Memoize media data map for O(1) lookups
  const mediaDataMap = useMemo(() => {
    const map = new Map();
    data?.data?.forEach(media => {
      map.set(media.id, media);
    });
    return map;
  }, [data?.data]);

  // Selected IDs that are currently decorative (for Unmark bulk button).
  const selectedDecorativeIds = useMemo(() => {
    const ids = [];
    selectedMedia.forEach((id) => {
      const media = mediaDataMap.get(id);
      if (media && media.alt_category === 'decorative') ids.push(id);
    });
    return ids;
  }, [selectedMedia, mediaDataMap]);

  // Memoize alt text values map
  const altTextMap = useMemo(() => {
    const map = new Map();
    data?.data?.forEach(media => {
      const altText = editedAltTexts[media.id] !== undefined 
        ? editedAltTexts[media.id] 
        : media.recommended_alt_text || '';
      map.set(media.id, altText);
    });
    return map;
  }, [data?.data, editedAltTexts]);

  const handleSelectAll = useCallback((event) => {
    const checked = event.target.checked;
    setSelectedMedia(prev => {
      const newSet = new Set(prev);
      if (checked) {
        currentPageMediaIds.forEach(id => newSet.add(id));
      } else {
        currentPageMediaIds.forEach(id => newSet.delete(id));
      }
      return newSet;
    });
  }, [currentPageMediaIds]);

  const handleSelectMedia = useCallback((mediaId) => {
    setSelectedMedia(prev => {
      const newSet = new Set(prev);
      if (newSet.has(mediaId)) {
        newSet.delete(mediaId);
      } else {
        newSet.add(mediaId);
      }
      return newSet;
    });
  }, []);

  const handleMediaTypeChange = (mediaTypeId) => {
    setSelectedMediaTypes(prev => {
      if (prev.includes(mediaTypeId)) {
        return prev.filter(id => id !== mediaTypeId);
      }
      return [...prev, mediaTypeId];
    });
  };

  const handleAltCategoryChange = (event) => {
    const value = event.target.value;
    setAltCategory(value);
    setPage(1);
    const next = new URLSearchParams(searchParams);
    if (value === 'all') {
      next.delete('alt_category');
    } else {
      next.set('alt_category', value);
    }
    setSearchParams(next);
  };

  const handleAltTextChange = useCallback((mediaId, value) => {
    setEditedAltTexts(prev => ({
      ...prev,
      [mediaId]: value,
    }));
  }, []);

  const handleGenerateAltText = useCallback(async (singleMediaId) => {
    const id = singleMediaId != null && typeof singleMediaId === 'number' ? singleMediaId : null;
    const mediaIds = id !== null ? [id] : Array.from(selectedMedia);
    if (mediaIds.length === 0) return;
    try {
      const response = await generateMutation.mutateAsync({ mediaIds, async: false });
      const results = response?.data ?? response;
      setEditedAltTexts((prev) => {
        const next = { ...prev };
        (Array.isArray(results) ? results : []).forEach((r) => {
          if (r.success && r.media_id != null) {
            next[r.media_id] = r.alt_text ?? '';
          }
        });
        return next;
      });
      if (!singleMediaId) setSelectedMedia(new Set());
    } catch (error) {
      console.error('Failed to generate alt text:', error);
    }
  }, [selectedMedia, generateMutation]);

  const handleApplyAltText = useCallback(async () => {
    if (selectedMedia.size === 0) return;
    
    try {
      // Prepare alt text map: use edited text if available, otherwise use recommended
      const altTextMapObj = {};
      const mediaIds = Array.from(selectedMedia);
      
      // Use mediaDataMap for O(1) lookups
      mediaIds.forEach(mediaId => {
        const media = mediaDataMap.get(mediaId);
        if (media) {
          const altText = editedAltTexts[mediaId] !== undefined 
            ? editedAltTexts[mediaId] 
            : media.recommended_alt_text || '';
          if (altText !== '') {
            altTextMapObj[mediaId] = altText;
          }
        }
      });

      await applyMutation.mutateAsync({ 
        mediaIds,
        altTexts: altTextMapObj,
      });
      setSelectedMedia(new Set());
      // Clear edited texts for applied items
      setEditedAltTexts(prev => {
        const updated = { ...prev };
        mediaIds.forEach(id => delete updated[id]);
        return updated;
      });
    } catch (error) {
      console.error('Failed to apply alt text:', error);
    }
  }, [selectedMedia, mediaDataMap, editedAltTexts, applyMutation]);

  const handleBatchGenerate = useCallback(async () => {
    if (selectedMedia.size === 0) return;
    try {
      const mediaIds = Array.from(selectedMedia);
      await batchGenerateMutation.mutateAsync({ mediaIds, batchSize: 10 });
      setSelectedMedia(new Set());
    } catch (error) {
      console.error('Failed to schedule batch generation:', error);
    }
  }, [selectedMedia, batchGenerateMutation]);

  const handleMarkDecorative = useCallback(async (mediaIdsToMark) => {
    const ids = Array.isArray(mediaIdsToMark) ? mediaIdsToMark : Array.from(mediaIdsToMark);
    if (ids.length === 0) return;
    try {
      await setAltCategoryMutation.mutateAsync({ mediaIds: ids, altCategory: 'decorative' });
      setSelectedMedia(new Set());
      setMarkDecorativeConfirmOpen(false);
      setEditedAltTexts((prev) => {
        const next = { ...prev };
        ids.forEach((id) => delete next[id]);
        return next;
      });
    } catch (error) {
      console.error('Failed to mark as decorative:', error);
    }
  }, [setAltCategoryMutation]);

  const handleMarkDecorativeBulk = useCallback(() => {
    if (selectedMedia.size === 0) return;
    setMarkDecorativeConfirmOpen(true);
  }, [selectedMedia.size]);

  const confirmMarkDecorativeBulk = useCallback(() => {
    handleMarkDecorative(Array.from(selectedMedia));
  }, [selectedMedia, handleMarkDecorative]);

  const handleUnmarkDecorative = useCallback(async (mediaIdsToUnmark) => {
    const ids = Array.isArray(mediaIdsToUnmark) ? mediaIdsToUnmark : Array.from(mediaIdsToUnmark);
    if (ids.length === 0) return;
    try {
      await setAltCategoryMutation.mutateAsync({ mediaIds: ids, altCategory: '' });
      setSelectedMedia(new Set());
    } catch (err) {
      console.error('Failed to unmark decorative:', err);
    }
  }, [setAltCategoryMutation]);

  const handleUnmarkDecorativeBulk = useCallback(() => {
    if (selectedDecorativeIds.length === 0) return;
    handleUnmarkDecorative(selectedDecorativeIds);
  }, [selectedDecorativeIds, handleUnmarkDecorative]);

  const getStatusColor = useCallback((status) => {
    switch (status) {
      case 'completed':
        return 'success';
      case 'processing':
        return 'info';
      case 'error':
        return 'error';
      default:
        return 'default';
    }
  }, []);

  const getStatusLabel = useCallback((status) => {
    switch (status) {
      case 'completed':
        return __('Completed', 'flux-ai-media-alt-creator');
      case 'processing':
        return __('Processing', 'flux-ai-media-alt-creator');
      case 'error':
        return __('Error', 'flux-ai-media-alt-creator');
      default:
        return __('Pending', 'flux-ai-media-alt-creator');
    }
  }, []);

  const getCategoryLabel = useCallback((cat) => {
    const o = ALT_CATEGORY_OPTIONS.find((x) => x.value === cat);
    return o ? o.label : cat || '—';
  }, []);

  if (error) {
    return (
      <Alert severity="error">
        {__('Failed to load media files. Please try again.', 'flux-ai-media-alt-creator')}
      </Alert>
    );
  }

  const hasMultipleMediaTypes = !loadingGroups && mediaTypeGroups && mediaTypeGroups.length > 1;
  const isSingleMediaType = !loadingGroups && mediaTypeGroups && mediaTypeGroups.length === 1;

  return (
    <Box>
      <Stack direction="row" spacing={2} sx={{ mb: 3, alignItems: 'center', justifyContent: 'space-between' }}>
        <Typography variant="h5">
          {__('Media Files', 'flux-ai-media-alt-creator')}
        </Typography>
      </Stack>

      {/* Category filter, Media Type, Search */}
      <Grid container spacing={2} sx={{ mb: 3, alignItems: 'center' }}>
        <Grid item xs={12} sm="auto">
          <FormControl size="small" sx={{ minWidth: 180 }}>
            <InputLabel>{__('Category', 'flux-ai-media-alt-creator')}</InputLabel>
            <Select
              value={altCategory}
              label={__('Category', 'flux-ai-media-alt-creator')}
              onChange={handleAltCategoryChange}
            >
              {ALT_CATEGORY_OPTIONS.filter((o) => o.value !== 'woocommerce' || isWooCommerceActive).map((o) => (
                <MenuItem key={o.value} value={o.value}>{o.label}</MenuItem>
              ))}
            </Select>
          </FormControl>
        </Grid>
        {!loadingGroups && mediaTypeGroups && mediaTypeGroups.length > 0 && (
          <Grid item xs={12} sm="auto">
            <FormGroup row>
              {mediaTypeGroups.map((group) => (
                <FormControlLabel
                  key={group.id}
                  control={
                    <Checkbox
                      checked={selectedMediaTypes.includes(group.id)}
                      onChange={() => handleMediaTypeChange(group.id)}
                      disabled={isSingleMediaType}
                    />
                  }
                  label={group.label}
                />
              ))}
            </FormGroup>
          </Grid>
        )}
        <Grid item xs={12} sm>
          <TextField
            placeholder={__('Search media files...', 'flux-ai-media-alt-creator')}
            value={search}
            onChange={(e) => {
              setSearch(e.target.value);
              setPage(1);
            }}
            size="small"
          />
        </Grid>
      </Grid>

      {/* Action Buttons with Selection Indicator */}
      <Grid container spacing={2} sx={{ mb: 2 }} alignItems="center">
        <Grid item xs="auto">
          <Button
            variant="contained"
            onClick={() => handleGenerateAltText()}
            disabled={selectedMedia.size === 0 || generateMutation.isPending}
          >
            {generateMutation.isPending ? <CircularProgress size={20} /> : __('Generate AI Alt Text', 'flux-ai-media-alt-creator')}
          </Button>
        </Grid>
        <Grid item xs="auto">
          <Button
            variant="outlined"
            onClick={handleApplyAltText}
            disabled={selectedMedia.size === 0 || applyMutation.isPending}
          >
            {applyMutation.isPending ? <CircularProgress size={20} /> : __('Apply Alt Text', 'flux-ai-media-alt-creator')}
          </Button>
        </Grid>
        <Grid item xs="auto">
          <Tooltip title={__('Use only for purely decorative images. Decorative images should have empty alt text per WCAG guidance.', 'flux-ai-media-alt-creator')}>
            <span>
              <Button
                variant="outlined"
                color="secondary"
                onClick={handleMarkDecorativeBulk}
                disabled={selectedMedia.size === 0 || setAltCategoryMutation.isPending}
              >
                {__('Mark Decorative', 'flux-ai-media-alt-creator')}
              </Button>
            </span>
          </Tooltip>
        </Grid>
        <Grid item xs="auto">
          <Tooltip title={__('Re-evaluate and assign the category these images would have if they were not marked decorative (e.g. missing).', 'flux-ai-media-alt-creator')}>
            <span>
              <Button
                variant="outlined"
                onClick={handleUnmarkDecorativeBulk}
                disabled={selectedDecorativeIds.length === 0 || setAltCategoryMutation.isPending}
              >
                {setAltCategoryMutation.isPending ? <CircularProgress size={20} /> : __('Unmark Decorative', 'flux-ai-media-alt-creator')}
              </Button>
            </span>
          </Tooltip>
        </Grid>
        <Grid item xs>
          <Typography variant="body2" color="text.secondary">
            {selectedMedia.size} {__('of', 'flux-ai-media-alt-creator')} {data?.total || 0} {__('selected', 'flux-ai-media-alt-creator')}
          </Typography>
        </Grid>
      </Grid>

      <Dialog open={markDecorativeConfirmOpen} onClose={() => setMarkDecorativeConfirmOpen(false)}>
        <DialogTitle>{__('Mark as decorative?', 'flux-ai-media-alt-creator')}</DialogTitle>
        <DialogContent>
          <Typography>
            {__('Mark', 'flux-ai-media-alt-creator')} {selectedMedia.size} {__('images as decorative? This will remove their alt text.', 'flux-ai-media-alt-creator')}
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setMarkDecorativeConfirmOpen(false)}>{__('Cancel', 'flux-ai-media-alt-creator')}</Button>
          <Button variant="contained" color="primary" onClick={confirmMarkDecorativeBulk} disabled={setAltCategoryMutation.isPending}>
            {__('Mark Decorative', 'flux-ai-media-alt-creator')}
          </Button>
        </DialogActions>
      </Dialog>

      {isLoading ? (
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell><Skeleton /></TableCell>
              <TableCell><Skeleton /></TableCell>
              <TableCell><Skeleton /></TableCell>
              <TableCell><Skeleton /></TableCell>
              <TableCell><Skeleton /></TableCell>
              <TableCell><Skeleton /></TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {[...Array(5)].map((_, i) => (
              <TableRow key={i}>
                <TableCell><Skeleton /></TableCell>
                <TableCell><Skeleton /></TableCell>
                <TableCell><Skeleton /></TableCell>
                <TableCell><Skeleton /></TableCell>
                <TableCell><Skeleton /></TableCell>
                <TableCell><Skeleton /></TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      ) : (
        <>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell padding="checkbox">
                  <Checkbox
                    indeterminate={selectedOnCurrentPageCount > 0 && selectedOnCurrentPageCount < currentPageMediaIds.size}
                    checked={currentPageMediaIds.size > 0 && selectedOnCurrentPageCount === currentPageMediaIds.size}
                    onChange={handleSelectAll}
                  />
                </TableCell>
                <TableCell>{__('Thumbnail', 'flux-ai-media-alt-creator')}</TableCell>
                <TableCell>{__('Filename', 'flux-ai-media-alt-creator')}</TableCell>
                <TableCell>{__('Alt Text', 'flux-ai-media-alt-creator')}</TableCell>
                <TableCell>{__('Proposed Alt Text', 'flux-ai-media-alt-creator')}</TableCell>
                <TableCell>{__('Category', 'flux-ai-media-alt-creator')}</TableCell>
                <TableCell>{__('Status', 'flux-ai-media-alt-creator')}</TableCell>
                <TableCell align="center">{__('Actions', 'flux-ai-media-alt-creator')}</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {(Array.isArray(data?.data) ? data.data : []).map((media) => {
                if (!media || media.id == null) return null;
                const isSelected = selectedMedia.has(media.id);
                const altText = altTextMap.get(media.id) ?? media.recommended_alt_text ?? '';
                const generatingMediaIds = generateMutation.isPending ? (generateMutation.variables?.mediaIds || []) : [];
                const isGenerating = generatingMediaIds.includes(media.id);

                return (
                  <MediaRow
                    key={media.id}
                    media={media}
                    isSelected={isSelected}
                    altText={altText}
                    mediaId={media.id}
                    isGenerating={isGenerating}
                    onSelect={handleSelectMedia}
                    onAltTextChange={handleAltTextChange}
                    getStatusLabel={getStatusLabel}
                    getStatusColor={getStatusColor}
                    getCategoryLabel={getCategoryLabel}
                    onMarkDecorative={handleMarkDecorative}
                    onUnmarkDecorative={handleUnmarkDecorative}
                    onGenerate={() => handleGenerateAltText(media.id)}
                    onApply={() => {
                      const text = editedAltTexts[media.id] !== undefined ? editedAltTexts[media.id] : (media.recommended_alt_text || '');
                      if (text !== '' && typeof applyMutation.mutate === 'function') {
                        applyMutation.mutate({ mediaIds: [media.id], altTexts: { [media.id]: text } });
                      }
                    }}
                    onMarkDecorativeTooltip={__('Use only for purely decorative images. Decorative images should have empty alt text per WCAG guidance.', 'flux-ai-media-alt-creator')}
                    applyPending={applyMutation.isPending}
                    generatePending={generateMutation.isPending}
                    setCategoryPending={setAltCategoryMutation.isPending}
                  />
                );
              })}
            </TableBody>
          </Table>

          {data?.total_pages > 1 && (
            <Box sx={{ display: 'flex', justifyContent: 'center', mt: 3 }}>
              <Pagination
                count={data.total_pages}
                page={page}
                onChange={(event, value) => setPage(value)}
                color="primary"
              />
            </Box>
          )}
        </>
      )}
    </Box>
  );
};

/**
 * Memoized table row component to prevent unnecessary re-renders
 */
const MediaRow = React.memo(({
  media = {},
  isSelected,
  altText,
  mediaId,
  isGenerating,
  onSelect,
  onAltTextChange,
  getStatusLabel,
  getStatusColor,
  getCategoryLabel,
  onMarkDecorative,
  onUnmarkDecorative,
  onGenerate,
  onApply,
  onMarkDecorativeTooltip,
  applyPending,
  generatePending,
  setCategoryPending,
}) => {
  const handleCheckboxChange = (event) => {
    event.stopPropagation();
    if (typeof onSelect === 'function') onSelect(mediaId);
  };

  const handleAltTextChange = (event) => {
    if (typeof onAltTextChange === 'function') onAltTextChange(mediaId, event.target.value);
  };

  const currentAlt = (media && media.current_alt != null) ? String(media.current_alt) : '';
  const categoryLabel = (media && media.alt_category && typeof getCategoryLabel === 'function')
    ? getCategoryLabel(media.alt_category)
    : '—';
  const statusLabel = typeof getStatusLabel === 'function' ? getStatusLabel(media.scan_status) : (media.scan_status || '—');
  const statusColor = typeof getStatusColor === 'function' ? getStatusColor(media.scan_status) : 'default';

  return (
    <TableRow>
      <TableCell padding="checkbox">
        <Checkbox checked={!!isSelected} onChange={handleCheckboxChange} />
      </TableCell>
      <TableCell>
        {media.thumbnail_url ? (
          <Link href={media.edit_url || '#'} target="_blank" rel="noopener noreferrer">
            <img src={media.thumbnail_url} alt="" style={{ width: 50, height: 50, objectFit: 'cover' }} />
          </Link>
        ) : (
          <Box sx={{ width: 50, height: 50, bgcolor: 'grey.200' }} />
        )}
      </TableCell>
      <TableCell>{media.filename ?? '—'}</TableCell>
      <TableCell sx={{ maxWidth: 180 }} title={currentAlt}>
        <Typography variant="body2" noWrap>{currentAlt || '—'}</Typography>
      </TableCell>
      <TableCell sx={{ minWidth: 180 }}>
        <TextField
          fullWidth
          size="small"
          value={altText}
          onChange={handleAltTextChange}
          placeholder={__('No recommendation yet', 'flux-ai-media-alt-creator')}
          variant="outlined"
          disabled={isGenerating}
          InputProps={{
            endAdornment: isGenerating ? (
              <InputAdornment position="end">
                <CircularProgress size={20} />
              </InputAdornment>
            ) : null,
          }}
        />
      </TableCell>
      <TableCell>
        {media.alt_category ? (
          <Chip label={categoryLabel} size="small" variant="outlined" />
        ) : (
          '—'
        )}
      </TableCell>
      <TableCell>
        <Chip
          label={statusLabel}
          color={statusColor}
          size="small"
        />
      </TableCell>
      <TableCell align="center" sx={{ width: 160 }}>
        <Stack direction="row" spacing={0.5} justifyContent="center" flexWrap="wrap" useFlexGap>
          <Tooltip title={__('Generate AI alt text', 'flux-ai-media-alt-creator')}>
            <span>
              <IconButton
                size="small"
                color="primary"
                onClick={typeof onGenerate === 'function' ? onGenerate : undefined}
                disabled={generatePending}
                aria-label={__('Generate', 'flux-ai-media-alt-creator')}
              >
                <AutoAwesome fontSize="small" />
              </IconButton>
            </span>
          </Tooltip>
          <Tooltip title={__('Apply proposed alt text', 'flux-ai-media-alt-creator')}>
            <span>
              <IconButton
                size="small"
                color="primary"
                onClick={typeof onApply === 'function' ? onApply : undefined}
                disabled={applyPending}
                aria-label={__('Apply', 'flux-ai-media-alt-creator')}
              >
                <CheckCircle fontSize="small" />
              </IconButton>
            </span>
          </Tooltip>
          {media.alt_category === 'decorative' ? (
            <Tooltip title={__('Re-evaluate and assign category from current alt (e.g. missing)', 'flux-ai-media-alt-creator')}>
              <span>
                <IconButton
                  size="small"
                  onClick={typeof onUnmarkDecorative === 'function' ? () => onUnmarkDecorative([mediaId]) : undefined}
                  disabled={setCategoryPending}
                  aria-label={__('Unmark Decorative', 'flux-ai-media-alt-creator')}
                >
                  <Restore fontSize="small" />
                </IconButton>
              </span>
            </Tooltip>
          ) : (
            <Tooltip title={onMarkDecorativeTooltip || ''}>
              <span>
                <IconButton
                  size="small"
                  onClick={typeof onMarkDecorative === 'function' ? () => onMarkDecorative([mediaId]) : undefined}
                  disabled={setCategoryPending}
                  aria-label={__('Mark Decorative', 'flux-ai-media-alt-creator')}
                >
                  <ImageOutlined fontSize="small" />
                </IconButton>
              </span>
            </Tooltip>
          )}
        </Stack>
      </TableCell>
    </TableRow>
  );
}, (prevProps, nextProps) => {
  const prev = prevProps.media || {};
  const next = nextProps.media || {};
  return (
    prevProps.mediaId === nextProps.mediaId &&
    prevProps.isSelected === nextProps.isSelected &&
    prevProps.altText === nextProps.altText &&
    prevProps.isGenerating === nextProps.isGenerating &&
    prevProps.applyPending === nextProps.applyPending &&
    prevProps.generatePending === nextProps.generatePending &&
    prevProps.setCategoryPending === nextProps.setCategoryPending &&
    prev.scan_status === next.scan_status &&
    prev.filename === next.filename &&
    prev.thumbnail_url === next.thumbnail_url &&
    prev.edit_url === next.edit_url &&
    prev.alt_category === next.alt_category &&
    prev.current_alt === next.current_alt
  );
});

MediaRow.displayName = 'MediaRow';

export default MediaPage;
