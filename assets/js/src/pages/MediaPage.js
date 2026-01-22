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
} from '@mui/material';
import { __ } from '@wordpress/i18n';
import { useMedia, useMediaTypeGroups } from '../hooks/useMedia';
import { useGenerateAltText, useApplyAltText, useBatchGenerateAltText } from '../hooks/useAltText';

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
const MediaPage = () => {
  const [page, setPage] = useState(1);
  const [selectedMedia, setSelectedMedia] = useState(new Set()); // Use Set for O(1) lookups
  const [search, setSearch] = useState('');
  const [filters, setFilters] = useState({});
  const [selectedMediaTypes, setSelectedMediaTypes] = useState(['images']);
  const [editedAltTexts, setEditedAltTexts] = useState({}); // Store edited alt text per media ID
  const perPage = 20;

  // Fetch available media type groups
  const { data: mediaTypeGroups, isLoading: loadingGroups } = useMediaTypeGroups();
  
  // Auto-select and disable if only one media type
  useEffect(() => {
    if (!loadingGroups && mediaTypeGroups && mediaTypeGroups.length === 1) {
      setSelectedMediaTypes([mediaTypeGroups[0].id]);
    }
  }, [loadingGroups, mediaTypeGroups]);

  // Update filters when media types change
  useEffect(() => {
    if (selectedMediaTypes.length > 0) {
      setFilters(prev => ({
        ...prev,
        media_types: selectedMediaTypes,
      }));
    } else {
      setFilters(prev => ({
        ...prev,
        media_types: ['images'],
      }));
    }
  }, [selectedMediaTypes]);

  // Clear selections when page changes
  useEffect(() => {
    setSelectedMedia(new Set());
  }, [page]);

  const { data, isLoading, error } = useMedia(page, perPage, search, filters);
  const generateMutation = useGenerateAltText();
  const applyMutation = useApplyAltText();
  const batchGenerateMutation = useBatchGenerateAltText();

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
      } else {
        return [...prev, mediaTypeId];
      }
    });
  };

  const handleAltTextChange = useCallback((mediaId, value) => {
    setEditedAltTexts(prev => ({
      ...prev,
      [mediaId]: value,
    }));
  }, []);

  const handleGenerateAltText = useCallback(async () => {
    if (selectedMedia.size === 0) return;
    
    try {
      const mediaIds = Array.from(selectedMedia);
      await generateMutation.mutateAsync({ mediaIds, async: false });
      setSelectedMedia(new Set());
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

      {/* Media Type Filters and Search - Inline */}
      <Grid container spacing={2} sx={{ mb: 3, alignItems: 'center' }}>
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
            onClick={handleGenerateAltText}
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
        <Grid item xs>
          <Typography variant="body2" color="text.secondary">
            {selectedMedia.size} {__('of', 'flux-ai-media-alt-creator')} {data?.total || 0} {__('selected', 'flux-ai-media-alt-creator')}
          </Typography>
        </Grid>
      </Grid>

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
                <TableCell>{__('AI Status', 'flux-ai-media-alt-creator')}</TableCell>
                <TableCell>{__('Alt Text', 'flux-ai-media-alt-creator')}</TableCell>
                <TableCell>{__('Actions', 'flux-ai-media-alt-creator')}</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {data?.data?.map((media) => {
                const isSelected = selectedMedia.has(media.id);
                const altText = altTextMap.get(media.id) || '';
                
                return (
                  <MediaRow
                    key={media.id}
                    media={media}
                    isSelected={isSelected}
                    altText={altText}
                    mediaId={media.id}
                    onSelect={handleSelectMedia}
                    onAltTextChange={handleAltTextChange}
                    getStatusLabel={getStatusLabel}
                    getStatusColor={getStatusColor}
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
  media, 
  isSelected, 
  altText, 
  mediaId,
  onSelect, 
  onAltTextChange,
  getStatusLabel,
  getStatusColor,
}) => {
  const handleCheckboxChange = (event) => {
    event.stopPropagation();
    onSelect(mediaId);
  };

  const handleAltTextChange = (event) => {
    onAltTextChange(mediaId, event.target.value);
  };

  return (
    <TableRow>
      <TableCell padding="checkbox">
        <Checkbox
          checked={isSelected}
          onChange={handleCheckboxChange}
        />
      </TableCell>
      <TableCell>
        {media.thumbnail_url ? (
          <img src={media.thumbnail_url} alt="" style={{ width: 50, height: 50, objectFit: 'cover' }} />
        ) : (
          <Box sx={{ width: 50, height: 50, bgcolor: 'grey.200' }} />
        )}
      </TableCell>
      <TableCell>{media.filename}</TableCell>
      <TableCell>
        <Chip
          label={getStatusLabel(media.scan_status)}
          color={getStatusColor(media.scan_status)}
          size="small"
        />
      </TableCell>
      <TableCell>
        <TextField
          fullWidth
          size="small"
          value={altText}
          onChange={handleAltTextChange}
          placeholder={__('No recommendation yet', 'flux-ai-media-alt-creator')}
          variant="outlined"
        />
      </TableCell>
      <TableCell>
        <Link href={media.edit_url} target="_blank" rel="noopener noreferrer">
          {__('Edit', 'flux-ai-media-alt-creator')}
        </Link>
      </TableCell>
    </TableRow>
  );
}, (prevProps, nextProps) => {
  // Custom comparison function for React.memo - only re-render if these change
  // Note: Function props (onSelect, onAltTextChange, getStatusLabel, getStatusColor) are stable via useCallback
  return (
    prevProps.mediaId === nextProps.mediaId &&
    prevProps.isSelected === nextProps.isSelected &&
    prevProps.altText === nextProps.altText &&
    prevProps.media.scan_status === nextProps.media.scan_status &&
    prevProps.media.filename === nextProps.media.filename &&
    prevProps.media.thumbnail_url === nextProps.media.thumbnail_url &&
    prevProps.media.edit_url === nextProps.media.edit_url
  );
});

MediaRow.displayName = 'MediaRow';

export default MediaPage;
