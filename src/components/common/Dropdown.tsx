import styled from 'styled-components';
import { useState } from 'react';

interface Option {
  value: string;
  label: string;
}

interface DropdownProps {
  options: Option[];
  value: string;
  onChange: (value: string) => void;
}

const Select = styled.select`
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  min-width: 200px;
`;

export const Dropdown = ({ options, value, onChange }: DropdownProps) => (
  <Select value={value} onChange={e => onChange(e.target.value)}>
    {options.map(option => (
      <option key={option.value} value={option.value}>
        {option.label}
      </option>
    ))}
  </Select>
);
