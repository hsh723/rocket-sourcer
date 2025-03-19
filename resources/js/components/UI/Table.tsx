import {
  Table as MuiTable,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TablePagination,
  Paper,
  TableSortLabel,
} from '@mui/material';
import { useState } from 'react';

interface Column<T> {
  id: keyof T | string;
  label: string;
  render?: (value: any, row: T) => React.ReactNode;
  sortable?: boolean;
}

interface TableProps<T> {
  columns: Column<T>[];
  data: T[];
  onRowClick?: (row: T) => void;
  defaultSort?: {
    field: keyof T | string;
    order: 'asc' | 'desc';
  };
  pagination?: boolean;
  rowsPerPageOptions?: number[];
}

export function Table<T>({
  columns,
  data,
  onRowClick,
  defaultSort,
  pagination = true,
  rowsPerPageOptions = [10, 25, 50],
}: TableProps<T>) {
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(rowsPerPageOptions[0]);
  const [sort, setSort] = useState(defaultSort || { field: '', order: 'asc' as const });

  const handleSort = (field: keyof T | string) => {
    const isAsc = sort.field === field && sort.order === 'asc';
    setSort({
      field,
      order: isAsc ? 'desc' : 'asc',
    });
  };

  const sortData = (data: T[]) => {
    if (!sort.field) return data;

    return [...data].sort((a: any, b: any) => {
      const aValue = a[sort.field];
      const bValue = b[sort.field];

      if (aValue === bValue) return 0;
      if (aValue === null || aValue === undefined) return 1;
      if (bValue === null || bValue === undefined) return -1;

      const comparison = aValue < bValue ? -1 : 1;
      return sort.order === 'asc' ? comparison : -comparison;
    });
  };

  const sortedData = sortData(data);
  const paginatedData = pagination
    ? sortedData.slice(page * rowsPerPage, (page + 1) * rowsPerPage)
    : sortedData;

  return (
    <Paper>
      <TableContainer>
        <MuiTable>
          <TableHead>
            <TableRow>
              {columns.map((column) => (
                <TableCell key={column.id as string}>
                  {column.sortable !== false ? (
                    <TableSortLabel
                      active={sort.field === column.id}
                      direction={sort.field === column.id ? sort.order : 'asc'}
                      onClick={() => handleSort(column.id)}
                    >
                      {column.label}
                    </TableSortLabel>
                  ) : (
                    column.label
                  )}
                </TableCell>
              ))}
            </TableRow>
          </TableHead>
          <TableBody>
            {paginatedData.map((row, index) => (
              <TableRow
                key={index}
                hover={Boolean(onRowClick)}
                onClick={() => onRowClick?.(row)}
                sx={{ cursor: onRowClick ? 'pointer' : 'default' }}
              >
                {columns.map((column) => (
                  <TableCell key={column.id as string}>
                    {column.render
                      ? column.render(row[column.id as keyof T], row)
                      : row[column.id as keyof T]}
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </MuiTable>
      </TableContainer>
      {pagination && (
        <TablePagination
          rowsPerPageOptions={rowsPerPageOptions}
          component="div"
          count={data.length}
          rowsPerPage={rowsPerPage}
          page={page}
          onPageChange={(_, newPage) => setPage(newPage)}
          onRowsPerPageChange={(e) => {
            setRowsPerPage(parseInt(e.target.value, 10));
            setPage(0);
          }}
        />
      )}
    </Paper>
  );
} 