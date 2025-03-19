import { describe, expect, it, beforeEach, jest } from '@jest/globals';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { BrowserRouter } from 'react-router-dom';
import ProductList from '@/components/Products/ProductList';
import { ProductContext } from '@/context/ProductContext';

describe('ProductList', () => {
  const mockProducts = [
    {
      id: '1',
      name: '테스트 상품 1',
      price: 10000,
      description: '테스트 설명 1',
      category: '전자기기',
      rating: 4.5,
      reviewCount: 100
    },
    {
      id: '2',
      name: '테스트 상품 2',
      price: 20000,
      description: '테스트 설명 2',
      category: '의류',
      rating: 4.0,
      reviewCount: 50
    }
  ];

  const mockFetchProducts = jest.fn().mockResolvedValue(mockProducts);
  const mockDeleteProduct = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  const renderProductList = () => {
    return render(
      <BrowserRouter>
        <ProductContext.Provider value={{ 
          products: mockProducts,
          fetchProducts: mockFetchProducts,
          deleteProduct: mockDeleteProduct
        }}>
          <ProductList />
        </ProductContext.Provider>
      </BrowserRouter>
    );
  };

  it('제품 목록이 올바르게 렌더링되어야 합니다', async () => {
    renderProductList();

    await waitFor(() => {
      expect(screen.getByText('테스트 상품 1')).toBeInTheDocument();
      expect(screen.getByText('테스트 상품 2')).toBeInTheDocument();
    });
  });

  it('검색 필터가 작동해야 합니다', async () => {
    renderProductList();

    const searchInput = screen.getByPlaceholderText(/검색/i);
    await userEvent.type(searchInput, '테스트 상품 1');

    await waitFor(() => {
      expect(screen.getByText('테스트 상품 1')).toBeInTheDocument();
      expect(screen.queryByText('테스트 상품 2')).not.toBeInTheDocument();
    });
  });

  it('카테고리 필터가 작동해야 합니다', async () => {
    renderProductList();

    const categorySelect = screen.getByLabelText(/카테고리/i);
    await userEvent.selectOptions(categorySelect, '전자기기');

    await waitFor(() => {
      expect(screen.getByText('테스트 상품 1')).toBeInTheDocument();
      expect(screen.queryByText('테스트 상품 2')).not.toBeInTheDocument();
    });
  });

  it('제품 삭제 기능이 작동해야 합니다', async () => {
    renderProductList();

    const deleteButton = screen.getAllByRole('button', { name: /삭제/i })[0];
    await userEvent.click(deleteButton);

    // 확인 대화상자
    const confirmButton = screen.getByRole('button', { name: /확인/i });
    await userEvent.click(confirmButton);

    await waitFor(() => {
      expect(mockDeleteProduct).toHaveBeenCalledWith('1');
    });
  });
}); 