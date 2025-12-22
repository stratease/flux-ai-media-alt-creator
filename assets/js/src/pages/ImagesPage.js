import React, { useState } from 'react';
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
} from '@mui/material';
import { __ } from '@wordpress/i18n';
import { useImages } from '../hooks/useImages';
import { useGenerateAltText, useApplyAltText, useBatchGenerateAltText } from '../hooks/useAltText';

/**
 * Images page component with paginated list and bulk actions
 */
const ImagesPage = () => {
  const [page, setPage] = useState(1);
  const [selectedImages, setSelectedImages] = useState([]);
  const [search, setSearch] = useState('');
  const perPage = 20;

  const { data, isLoading, error } = useImages(page, perPage, search);
  const generateMutation = useGenerateAltText();
  const applyMutation = useApplyAltText();
  const batchGenerateMutation = useBatchGenerateAltText();

  const handleSelectAll = (event) => {
    if (event.target.checked) {
      setSelectedImages(data?.data?.map(img => img.id) || []);
    } else {
      setSelectedImages([]);
    }
  };

  const handleSelectImage = (imageId) => {
    setSelectedImages(prev => {
      if (prev.includes(imageId)) {
        return prev.filter(id => id !== imageId);
      } else {
        return [...prev, imageId];
      }
    });
  };

  const handleGenerateAltText = async () => {
    if (selectedImages.length === 0) return;
    
    try {
      await generateMutation.mutateAsync({ imageIds: selectedImages, async: false });
      setSelectedImages([]);
    } catch (error) {
      console.error('Failed to generate alt text:', error);
    }
  };

  const handleApplyAltText = async () => {
    if (selectedImages.length === 0) return;
    
    try {
      await applyMutation.mutateAsync(selectedImages);
      setSelectedImages([]);
    } catch (error) {
      console.error('Failed to apply alt text:', error);
    }
  };

  const handleBatchGenerate = async () => {
    if (selectedImages.length === 0) return;
    
    try {
      await batchGenerateMutation.mutateAsync({ imageIds: selectedImages, batchSize: 10 });
      setSelectedImages([]);
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
        {__('Failed to load images. Please try again.', 'flux-ai-media-alt-creator')}
      </Alert>
    );
  }

  return (
    <Box>
      <Stack direction="row" spacing={2} sx={{ mb: 3, alignItems: 'center', justifyContent: 'space-between' }}>
        <Typography variant="h5">
          {__('Images Without Alt Text', 'flux-ai-media-alt-creator')}
        </Typography>
        <Stack direction="row" spacing={2}>
          <Button
            variant="contained"
            onClick={handleGenerateAltText}
            disabled={selectedImages.length === 0 || generateMutation.isPending}
          >
            {generateMutation.isPending ? <CircularProgress size={20} /> : __('Generate AI Alt Text', 'flux-ai-media-alt-creator')}
          </Button>
          <Button
            variant="contained"
            color="secondary"
            onClick={handleBatchGenerate}
            disabled={selectedImages.length === 0 || batchGenerateMutation.isPending}
          >
            {batchGenerateMutation.isPending ? <CircularProgress size={20} /> : __('Generate in Background', 'flux-ai-media-alt-creator')}
          </Button>
          <Button
            variant="outlined"
            onClick={handleApplyAltText}
            disabled={selectedImages.length === 0 || applyMutation.isPending}
          >
            {applyMutation.isPending ? <CircularProgress size={20} /> : __('Apply Alt Text', 'flux-ai-media-alt-creator')}
          </Button>
        </Stack>
      </Stack>

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
                      indeterminate={selectedImages.length > 0 && selectedImages.length < (data?.data?.length || 0)}
                      checked={data?.data?.length > 0 && selectedImages.length === data?.data?.length}
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
                {data?.data?.map((image) => (
                  <TableRow key={image.id}>
                    <TableCell padding="checkbox">
                      <Checkbox
                        checked={selectedImages.includes(image.id)}
                        onChange={() => handleSelectImage(image.id)}
                      />
                    </TableCell>
                    <TableCell>
                      {image.thumbnail_url ? (
                        <img src={image.thumbnail_url} alt="" style={{ width: 50, height: 50, objectFit: 'cover' }} />
                      ) : (
                        <Box sx={{ width: 50, height: 50, bgcolor: 'grey.200' }} />
                      )}
                    </TableCell>
                    <TableCell>{image.filename}</TableCell>
                    <TableCell>
                      <Chip
                        label={getStatusLabel(image.ai_status)}
                        color={getStatusColor(image.ai_status)}
                        size="small"
                      />
                    </TableCell>
                    <TableCell>
                      {image.recommended_alt_text || (
                        <Typography variant="body2" color="text.secondary">
                          {__('No recommendation yet', 'flux-ai-media-alt-creator')}
                        </Typography>
                      )}
                    </TableCell>
                    <TableCell>
                      <Link href={image.edit_url} target="_blank" rel="noopener noreferrer">
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

export default ImagesPage;

