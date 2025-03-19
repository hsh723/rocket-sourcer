import React from 'react';
import {
  Box,
  ImageList,
  ImageListItem,
  Modal,
  IconButton,
  Paper,
  Typography,
  CircularProgress
} from '@mui/material';
import {
  Close as CloseIcon,
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
  ZoomIn as ZoomInIcon
} from '@mui/icons-material';
import { useQuery } from '@tanstack/react-query';
import { productService } from '@/services/productService';

interface ProductImageGalleryProps {
  productId: string;
}

interface ProductImage {
  id: string;
  url: string;
  thumbnail: string;
  title: string;
}

const ProductImageGallery: React.FC<ProductImageGalleryProps> = ({ productId }) => {
  const [selectedImage, setSelectedImage] = React.useState<ProductImage | null>(null);
  const [zoomLevel, setZoomLevel] = React.useState(1);

  const { data: images, isLoading, error } = useQuery({
    queryKey: ['productImages', productId],
    queryFn: () => productService.getProductImages(productId)
  });

  const handleImageClick = (image: ProductImage) => {
    setSelectedImage(image);
    setZoomLevel(1);
  };

  const handleClose = () => {
    setSelectedImage(null);
    setZoomLevel(1);
  };

  const handlePrevious = () => {
    if (!images) return;
    const currentIndex = images.findIndex(img => img.id === selectedImage?.id);
    const previousIndex = (currentIndex - 1 + images.length) % images.length;
    setSelectedImage(images[previousIndex]);
    setZoomLevel(1);
  };

  const handleNext = () => {
    if (!images) return;
    const currentIndex = images.findIndex(img => img.id === selectedImage?.id);
    const nextIndex = (currentIndex + 1) % images.length;
    setSelectedImage(images[nextIndex]);
    setZoomLevel(1);
  };

  const handleZoom = () => {
    setZoomLevel(prev => (prev === 1 ? 2 : 1));
  };

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
        <CircularProgress />
      </Box>
    );
  }

  if (error) {
    return (
      <Typography color="error">
        이미지를 불러오는데 실패했습니다.
      </Typography>
    );
  }

  if (!images) return null;

  return (
    <Box>
      <ImageList cols={3} gap={8}>
        {images.map((image) => (
          <ImageListItem
            key={image.id}
            sx={{
              cursor: 'pointer',
              '&:hover': {
                opacity: 0.8
              }
            }}
            onClick={() => handleImageClick(image)}
          >
            <img
              src={image.thumbnail}
              alt={image.title}
              loading="lazy"
              style={{ height: '200px', objectFit: 'cover' }}
            />
          </ImageListItem>
        ))}
      </ImageList>

      <Modal
        open={Boolean(selectedImage)}
        onClose={handleClose}
        sx={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center'
        }}
      >
        <Paper
          sx={{
            position: 'relative',
            maxWidth: '90vw',
            maxHeight: '90vh',
            overflow: 'hidden'
          }}
        >
          <Box sx={{ position: 'absolute', right: 8, top: 8, zIndex: 1 }}>
            <IconButton onClick={handleZoom} sx={{ mr: 1 }}>
              <ZoomInIcon />
            </IconButton>
            <IconButton onClick={handleClose}>
              <CloseIcon />
            </IconButton>
          </Box>

          <Box
            sx={{
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              p: 2
            }}
          >
            <IconButton
              onClick={handlePrevious}
              sx={{ position: 'absolute', left: 8 }}
            >
              <ChevronLeftIcon />
            </IconButton>

            <Box
              sx={{
                overflow: 'hidden',
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center'
              }}
            >
              {selectedImage && (
                <img
                  src={selectedImage.url}
                  alt={selectedImage.title}
                  style={{
                    maxWidth: '100%',
                    maxHeight: '80vh',
                    transform: `scale(${zoomLevel})`,
                    transition: 'transform 0.3s ease'
                  }}
                />
              )}
            </Box>

            <IconButton
              onClick={handleNext}
              sx={{ position: 'absolute', right: 8 }}
            >
              <ChevronRightIcon />
            </IconButton>
          </Box>

          {selectedImage && (
            <Box sx={{ p: 2, textAlign: 'center' }}>
              <Typography variant="subtitle1">
                {selectedImage.title}
              </Typography>
            </Box>
          )}
        </Paper>
      </Modal>
    </Box>
  );
};

export default ProductImageGallery; 