export interface Reel {
  id: string;
  title: string;
  url: string;
  thumbnail: string;
  platform: 'instagram' | 'tiktok' | 'youtube' | 'other';
  displayOrder: number;
  visible: boolean;
}
