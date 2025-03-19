import React from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  IconButton,
  Tooltip,
  TablePagination,
  Chip
} from '@mui/material';
import {
  Edit as EditIcon,
  Delete as DeleteIcon,
  Analytics as AnalyticsIcon
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import { Product } from '@/types/product';

interface ProductTableProps {
  products: Product[];
  total: number;
  page: number;
  limit: number;
  onPageChange: (page: number) => void;
  onDelete?: (id: number) => void;
}

const ProductTable: React.FC<ProductTableProps> = ({
  products,
  total,
  page,
  limit,
  onPageChange,
  onDelete
}) => {
  const navigate = useNavigate();

  const handleChangePage = (event: unknown, newPage: number) => {
    onPageChange(newPage + 1);
  };

  const handleEdit = (id: number) => {
    navigate(`/products/${id}/edit`);
  };

  const handleAnalyze = (id: number) => {
    navigate(`/products/${id}/analysis`);
  };

  const handleDelete = (id: number) => {
    if (onDelete) {
      onDelete(id);
    }
  };

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('ko-KR', {
      style: 'currency',
      currency: 'KRW'
    }).format(price);
  };

  return (
    <Paper>
      <TableContainer>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>이미지</TableCell>
              <TableCell>제품명</TableCell>
              <TableCell>카테고리</TableCell>
              <TableCell align="right">판매가</TableCell>
              <TableCell align="right">원가</TableCell>
              <TableCell align="right">마진율</TableCell>
              <TableCell>상태</TableCell>
              <TableCell align="center">작업</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {products.map((product) => (
              <TableRow key={product.id}>
                <TableCell>
                  <img
                    src={product.thumbnail}
                    alt={product.name}
                    style={{ width: 50, height: 50, objectFit: 'cover' }}
                  />
                </TableCell>
                <TableCell>{product.name}</TableCell>
                <TableCell>{product.category}</TableCell>
                <TableCell align="right">
                  {formatPrice(product.sellingPrice)}
                </TableCell>
                <TableCell align="right">
                  {formatPrice(product.costPrice)}
                </TableCell>
                <TableCell align="right">
                  {((product.sellingPrice - product.costPrice) / product.sellingPrice * 100).toFixed(1)}%
                </TableCell>
                <TableCell>
                  <Chip
                    label={product.status}
                    color={
                      product.status === 'active' ? 'success' :
                      product.status === 'draft' ? 'default' : 'error'
                    }
                    size="small"
                  />
                </TableCell>
                <TableCell align="center">
                  <Tooltip title="분석">
                    <IconButton
                      size="small"
                      onClick={() => handleAnalyze(product.id)}
                    >
                      <AnalyticsIcon />
                    </IconButton>
                  </Tooltip>
                  <Tooltip title="수정">
                    <IconButton
                      size="small"
                      onClick={() => handleEdit(product.id)}
                    >
                      <EditIcon />
                    </IconButton>
                  </Tooltip>
                  <Tooltip title="삭제">
                    <IconButton
                      size="small"
                      onClick={() => handleDelete(product.id)}
                    >
                      <DeleteIcon />
                    </IconButton>
                  </Tooltip>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
      <TablePagination
        component="div"
        count={total}
        page={page - 1}
        rowsPerPage={limit}
        onPageChange={handleChangePage}
        rowsPerPageOptions={[10]}
      />
    </Paper>
  );
};

export default ProductTable; 