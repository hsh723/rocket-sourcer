import styled from 'styled-components';

interface Column {
  key: string;
  title: string;
}

interface DataTableProps {
  columns: Column[];
  data: any[];
}

const Table = styled.table`
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
`;

const Th = styled.th`
  padding: 1rem;
  background-color: #f5f5f5;
  text-align: left;
`;

export const DataTable = ({ columns, data }: DataTableProps) => (
  <Table>
    <thead>
      <tr>
        {columns.map(column => (
          <Th key={column.key}>{column.title}</Th>
        ))}
      </tr>
    </thead>
    <tbody>
      {data.map((row, i) => (
        <tr key={i}>
          {columns.map(column => (
            <td key={column.key}>{row[column.key]}</td>
          ))}
        </tr>
      ))}
    </tbody>
  </Table>
);
