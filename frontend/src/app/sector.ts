export interface Sector {
  id: number;
  name: string;
  children: Sector[];
}

export interface FlatSector {
  id: number;
  name: string;
  depth: number;
}

export interface Submission {
  name: string;
  sectorIds: number[];
  agreeToTerms: boolean;
}
