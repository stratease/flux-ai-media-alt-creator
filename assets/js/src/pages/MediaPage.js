import React, { useState, useEffect } from 'react';
import {
  Box,
  Typography,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
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
  const [selectedMedia, setSelectedMedia] = useState([]);
  const [search, setSearch] = useState('');
  // Filters state - can be extended by other plugins to add custom search parameters
  const [filters, setFilters] = useState({});
  const [selectedMediaTypes, setSelectedMediaTypes] = useState(['images']); // Default to images only
  const perPage = 20;

  // Fetch available media type groups
  const { data: mediaTypeGroups, isLoading: loadingGroups } = useMediaTypeGroups();
  
  // Update filters when media types change
  useEffect(() => {
    if (selectedMediaTypes.length > 0) {
      setFilters(prev => ({
        ...prev,
        media_types: selectedMediaTypes,
      }));
    } else {
      // If no types selected, default to images
      setFilters(prev => ({
        ...prev,
        media_types: ['images'],
      }));
    }
  }, [selectedMediaTypes]);

  const { data, isLoading, error } = useMedia(page, perPage, search, filters);
  const generateMutation = useGenerateAltText();
  const applyMutation = useApplyAltText();
  const batchGenerateMutation = useBatchGenerateAltText();

  const handleSelectAll = (event) => {
    if (event.target.checked) {
      setSelectedMedia(data?.data?.map(item => item.id) || []);
    } else {
      setSelectedMedia([]);
    }
  };

  const handleSelectMedia = (mediaId) => {
    setSelectedMedia(prev => {
      if (prev.includes(mediaId)) {
        return prev.filter(id => id !== mediaId);
      } else {
        return [...prev, mediaId];
      }
    });
  };

  const handleMediaTypeChange = (mediaTypeId) => {
    setSelectedMediaTypes(prev => {
      if (prev.includes(mediaTypeId)) {
        return prev.filter(id => id !== mediaTypeId);
      } else {
        return [...prev, mediaTypeId];
      }
    });
  };

  const handleGenerateAltText = async () => {
    if (selectedMedia.length === 0) return;
    
    try {
      await generateMutation.mutateAsync({ mediaIds: selectedMedia, async: false });
      setSelectedMedia([]);
    } catch (error) {
      console.error('Failed to generate alt text:', error);
    }
  };

  const handleApplyAltText = async () => {
    if (selectedMedia.length === 0) return;
    
    try {
      await applyMutation.mutateAsync(selectedMedia);
      setSelectedMedia([]);
    } catch (error) {
      console.error('Failed to apply alt text:', error);
    }
  };

  const handleBatchGenerate = async () => {
    if (selectedMedia.length === 0) return;
    
    try {
      await batchGenerateMutation.mutateAsync({ mediaIds: selectedMedia, batchSize: 10 });
      setSelectedMedia([]);
    } catch (error) {
      console.error('Failed to schedule batch generation:', error);
    }
  };

  const getStatusColor = (status) => {
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
  };

  const getStatusLabel = (status) => {
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
  };

  if (error) {
    return (
      <Alert severity="error">
        {__('Failed to load media files. Please try again.', 'flux-ai-media-alt-creator')}
      </Alert>
    );
  }

  return (
    <Box>
      <Stack direction="row" spacing={2} sx={{ mb: 3, alignItems: 'center', justifyContent: 'space-between' }}>
        <Typography variant="h5">
          {__('Media Files Without Alt Text', 'flux-ai-media-alt-creator')}
        </Typography>
        <Stack direction="row" spacing={2}>
          <Button
            variant="contained"
            onClick={handleGenerateAltText}
            disabled={selectedMedia.length === 0 || generateMutation.isPending}
          >
            {generateMutation.isPending ? <CircularProgress size={20} /> : __('Generate AI Alt Text', 'flux-ai-media-alt-creator')}
          </Button>
          <Button
            variant="contained"
            color="secondary"
            onClick={handleBatchGenerate}
            disabled={selectedMedia.length === 0 || batchGenerateMutation.isPending}
          >
            {batchGenerateMutation.isPending ? <CircularProgress size={20} /> : __('Generate in Background', 'flux-ai-media-alt-creator')}
          </Button>
          <Button
            variant="outlined"
            onClick={handleApplyAltText}
            disabled={selectedMedia.length === 0 || applyMutation.isPending}
          >
            {applyMutation.isPending ? <CircularProgress size={20} /> : __('Apply Alt Text', 'flux-ai-media-alt-creator')}
          </Button>
        </Stack>
      </Stack>

      {/* Media Type Filters */}
      {!loadingGroups && mediaTypeGroups && mediaTypeGroups.length > 0 && (
        <Paper sx={{ p: 2, mb: 3 }}>
          <Typography variant="subtitle2" sx={{ mb: 1 }}>
            {__('Filter by Media Type', 'flux-ai-media-alt-creator')}
          </Typography>
          <FormGroup row>
            {mediaTypeGroups.map((group) => (
              <FormControlLabel
                key={group.id}
                control={
                  <Checkbox
                    checked={selectedMediaTypes.includes(group.id)}
                    onChange={() => handleMediaTypeChange(group.id)}
                  />
                }
                label={group.label}
              />
            ))}
          </FormGroup>
        </Paper>
      )}

      {/* Search */}
      <Box sx={{ mb: 2 }}>
        <TextField
          fullWidth
          placeholder={__('Search media files...', 'flux-ai-media-alt-creator')}
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1); // Reset to first page on search
          }}
          size="small"
        />
      </Box>

      {isLoading ? (
        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
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
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      ) : (
        <>
          <TableContainer component={Paper}>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell padding="checkbox">
                    <Checkbox
                      indeterminate={selectedMedia.length > 0 && selectedMedia.length < (data?.data?.length || 0)}
                      checked={data?.data?.length > 0 && selectedMedia.length === data?.data?.length}
                      onChange={handleSelectAll}
                    />
                  </TableCell>
                  <TableCell>{__('Thumbnail', 'flux-ai-media-alt-creator')}</TableCell>
                  <TableCell>{__('Filename', 'flux-ai-media-alt-creator')}</TableCell>
                  <TableCell>{__('AI Status', 'flux-ai-media-alt-creator')}</TableCell>
                  <TableCell>{__('Recommended Alt Text', 'flux-ai-media-alt-creator')}</TableCell>
                  <TableCell>{__('Actions', 'flux-ai-media-alt-creator')}</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {data?.data?.map((media) => (
                  <TableRow key={media.id}>
                    <TableCell padding="checkbox">
                      <Checkbox
                        checked={selectedMedia.includes(media.id)}
                        onChange={() => handleSelectMedia(media.id)}
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
                        label={getStatusLabel(media.ai_status)}
                        color={getStatusColor(media.ai_status)}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      {media.recommended_alt_text || (
                        <Typography variant="body2" color="text.secondary">
                          {__('No recommendation yet', 'flux-ai-media-alt-creator')}
                        </Typography>
                      )}
                    </TableCell>
                    <TableCell>
                      <Link href={media.edit_url} target="_blank" rel="noopener noreferrer">
                        {__('Edit', 'flux-ai-media-alt-creator')}
                      </Link>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>

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

export default MediaPage;

